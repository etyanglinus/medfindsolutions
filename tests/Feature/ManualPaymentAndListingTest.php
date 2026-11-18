<?php

namespace Tests\Feature;

use App\Mail\PaybillPaymentRejected;
use App\Mail\ProviderPaymentConfirmed;
use App\Models\Admin;
use App\Models\ProviderPaymentTransaction;
use App\Services\ManualPaymentService;
use App\Services\PublicDirectory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Doctor\app\Models\Doctor;
use Modules\Doctor\app\Models\SubscriptionPaymentSetting;
use Tests\TestCase;

class ManualPaymentAndListingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Every page reads many settings; unknown ones read as blank here.
        Cache::put('setting', new class extends \stdClass {
            public $app_name = 'Medfind Test'; public $recaptcha_status = 'inactive'; public $version = '1.0'; public $cookie_status = 'inactive';
            public function __get($key) { return ''; }
            public function __isset($key) { return false; }
        });
        \Modules\Installer\app\Models\Configuration::create(['config' => 'setup_complete', 'value' => 1]);
        $this->withoutMiddleware([\App\Http\Middleware\DemoModeMiddleware::class, \App\Http\Middleware\SetLocaleMiddleware::class, \App\Http\Middleware\MaintenanceModeMiddleware::class, \App\Http\Middleware\TrackSiteVisit::class]);
        PublicDirectory::forget();
    }

    private function doctor(string $slug, ?string $expires = '+1 month', ?array $entitlements = ['search_visibility' => true], bool $emailConfirmed = true): Doctor
    {
        $department = DB::table('departments')->insertGetId(['slug' => 'dept-'.$slug, 'status' => 1]);
        $location = DB::table('locations')->insertGetId(['status' => 1]);
        $doctor = Doctor::create(['department_id' => $department, 'location_id' => $location, 'name' => ucfirst($slug), 'slug' => $slug, 'email' => "$slug@example.test", 'phone' => '0700', 'password' => bcrypt('x'), 'status' => 1, 'email_verified_at' => $emailConfirmed ? now() : null]);
        if ($expires) $doctor->subscriptions()->create(['package_name' => 'Basic', 'plan_code' => 'basic', 'package_price' => 1000, 'plan_duration' => 1, 'payment_method' => 'paystack', 'status' => 'approved', 'approved_at' => now()->subMonth(),
            'expires_at' => now()->modify($expires), 'transaction_code' => "T-$slug", 'entitlements' => $entitlements]);
        return $doctor;
    }

    private function admin(): Admin
    {
        $admin = new Admin;
        $admin->forceFill(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => bcrypt('x'), 'status' => 'active', 'is_super_admin' => 1])->save();
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    private function paybillOn(): void
    {
        SubscriptionPaymentSetting::create(['is_active' => true, 'bank_name' => 'Equity Bank', 'paybill_number' => '247247', 'account_number' => '0123456789', 'account_name' => 'MedFind Solutions']);
    }

    public function test_one_listing_rule_for_directory_profile_and_admin(): void
    {
        $paid = $this->doctor('paid-unconfirmed', emailConfirmed: false);   // paid but never clicked the email link
        $legacy = $this->doctor('legacy-plan', entitlements: null);          // older plan record without entitlements
        $expired = $this->doctor('expired', expires: '-1 day');
        $off = tap($this->doctor('switched-off'))->update(['status' => 0]);

        $listed = app(PublicDirectory::class)->doctors()->pluck('slug')->all();
        $this->assertEqualsCanonicalizing(['paid-unconfirmed', 'legacy-plan'], $listed);

        $this->get(route('website.doctor.details', 'paid-unconfirmed'))->assertOk();
        $this->get(route('website.doctor.details', 'expired'))->assertNotFound();
        $this->get(route('website.doctors'))->assertOk()->assertSee('Paid-unconfirmed')->assertSee('Legacy-plan')->assertDontSee('Expired');

        $status = app(PublicDirectory::class)->status('doctor', $expired);
        $this->assertFalse($status['listed']);
        $this->assertStringContainsString('Plan expired on', $status['reasons'][0]);
        $this->assertSame(['Account is switched off'], app(PublicDirectory::class)->status('doctor', $off)['reasons']);
        $this->assertTrue(app(PublicDirectory::class)->status('doctor', $paid)['listed']);
    }

    public function test_paybill_payment_is_submitted_then_approved_like_a_paystack_payment(): void
    {
        Mail::fake();
        $this->paybillOn();
        $doctor = $this->doctor('new-doctor', expires: null);
        $doctor->forceFill(['registration_token' => 'tok-1'])->save();

        $response = $this->post(route('provider.paystack.start', ['type' => 'doctor', 'token' => 'tok-1']), ['package' => 'premium', 'method' => 'paybill']);
        $payment = ProviderPaymentTransaction::sole();
        $response->assertRedirect(ManualPaymentService::paymentUrl($payment));
        $this->assertSame('mpesa_paybill', $payment->gateway);
        $this->assertSame('initiated', $payment->status);

        $page = ManualPaymentService::paymentUrl($payment);
        $this->get($page)->assertOk()->assertSee('247247')->assertSee('0123456789')->assertSee(number_format((float) $payment->amount, 0));
        $this->post(route('provider.paybill.submit', ['transaction' => $payment->id]), ['mpesa_code' => 'SKL3XYZ9AB'])->assertForbidden(); // unsigned
        $this->post($page, ['mpesa_code' => 'abc'])->assertSessionHasErrors('mpesa_code');
        $this->post($page, ['mpesa_code' => 'skl3 xyz9ab', 'payer_phone' => '0712345678'])->assertRedirect($page);
        $payment->refresh();
        $this->assertSame('pending', $payment->status);
        $this->assertSame('SKL3XYZ9AB', data_get($payment->gateway_payload, 'mpesa_code'));
        $this->get($page)->assertSee('Waiting for confirmation');

        $this->admin();
        $this->post(route('admin.provider-payments.paybill-approve', $payment), ['admin_note' => 'On statement 18 Nov'])->assertRedirect();
        $payment->refresh();
        $this->assertSame('paid', $payment->status);
        $this->assertSame('approved', $payment->subscription->status);
        $this->assertTrue($doctor->subscriptions()->where('payment_method', 'mpesa_paybill')->where('status', 'approved')->exists(), 'Mirrored into the specialist plan table like Paystack.');
        $this->assertTrue(in_array('new-doctor', app(PublicDirectory::class)->doctors()->pluck('slug')->all(), true), 'Listed once approved.');
        Mail::assertSent(ProviderPaymentConfirmed::class, fn ($m) => $m->reference === $payment->gateway_reference && $m->receiptUrl);

        // The same code cannot be used for another payment.
        $other = $this->doctor('other-doctor', expires: null);
        $second = app(ManualPaymentService::class)->startPaybill($other, 'doctor', 'premium');
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(ManualPaymentService::class)->submitCode($second, 'SKL3XYZ9AB');
    }

    public function test_rejected_paybill_code_goes_back_to_the_provider_with_the_reason(): void
    {
        Mail::fake();
        $this->paybillOn();
        $doctor = $this->doctor('rejected-doctor', expires: null);
        $manual = app(ManualPaymentService::class);
        $payment = $manual->submitCode($manual->startPaybill($doctor, 'doctor', 'premium'), 'QWE1234567');

        $this->admin();
        $this->post(route('admin.provider-payments.paybill-reject', $payment), [])->assertSessionHasErrors('reason');
        $this->post(route('admin.provider-payments.paybill-reject', $payment), ['reason' => 'Code not on the statement'])->assertRedirect();
        $payment->refresh();
        $this->assertSame('initiated', $payment->status);
        $this->assertSame('Code not on the statement', $payment->failure_reason);
        Mail::assertSent(PaybillPaymentRejected::class, fn ($m) => $m->reason === 'Code not on the statement');
        $this->get(ManualPaymentService::paymentUrl($payment))->assertSee('Code not on the statement');
        $manual->submitCode($payment, 'QWE1234568'); // corrected code accepted
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_paybill_option_appears_only_when_switched_on(): void
    {
        $doctor = $this->doctor('chooser', expires: null);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(ManualPaymentService::class)->startPaybill($doctor, 'doctor', 'premium');
    }

    public function test_admin_reactivates_an_expired_account(): void
    {
        Mail::fake();
        $doctor = $this->doctor('lapsed', expires: '-10 days');
        $this->assertFalse(app(PublicDirectory::class)->status('doctor', $doctor)['listed']);

        $this->admin();
        $this->post(route('admin.providers.activate-plan', ['type' => 'doctor', 'id' => $doctor->id]), ['package' => 'premium', 'amount' => 0, 'reference' => 'Cash 18 Nov'])
            ->assertRedirect()->assertSessionHas('alert-type', 'success');

        $payment = ProviderPaymentTransaction::sole();
        $this->assertSame(['admin', 'paid'], [$payment->gateway, $payment->status]);
        $this->assertTrue($payment->subscription->starts_at->isToday(), 'An expired account restarts today.');
        $this->assertTrue(app(PublicDirectory::class)->status('doctor', $doctor->fresh())['listed']);
        $this->assertSame(1, (int) $doctor->fresh()->status);
    }

    public function test_admin_pages_render(): void
    {
        $this->paybillOn();
        $this->doctor('page-doctor');
        $this->admin();
        $this->get(route('admin.doctor.index'))->assertOk()->assertSee('Public listing')->assertSee('Activate plan');
        $this->get(route('admin.provider-profiles.index', ['type' => 'supplier']))->assertOk()->assertSee('On the website');
        $this->get(route('admin.subscriptions.payment-details'))->assertOk()->assertSee('247247');
        $this->get(route('admin.provider-payments.index'))->assertOk();
    }

    public function test_employee_verifies_paybill_from_their_dashboard_but_cannot_activate_plans(): void
    {
        Mail::fake();
        $this->paybillOn();
        $doctor = $this->doctor('employee-check', expires: null);
        $manual = app(ManualPaymentService::class);
        $payment = $manual->submitCode($manual->startPaybill($doctor, 'doctor', 'premium'), 'EMP1234567');

        $employee = new Admin;
        $employee->forceFill(['name' => 'Sales Person', 'email' => 'sales@example.test', 'password' => bcrypt('x'), 'status' => 'active', 'is_super_admin' => 0])->save();
        $employee->assignRole(app(\App\Services\SalesRoleService::class)->ensure());
        $this->actingAs($employee, 'admin');

        $this->get(route('admin.dashboard'))->assertOk()->assertSee('M-Pesa payments to verify')->assertSee('EMP1234567')->assertSee('Payments to verify');
        $this->post(route('admin.providers.activate-plan', ['type' => 'doctor', 'id' => $doctor->id]), ['package' => 'premium', 'amount' => 0]);
        $this->assertSame(1, ProviderPaymentTransaction::count(), 'Employees cannot switch plans on by hand.');

        $this->from(route('admin.dashboard'))->post(route('admin.provider-payments.paybill-approve', $payment))->assertRedirect(route('admin.dashboard'));
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('Sales Person', data_get($payment->fresh()->gateway_payload, 'approved_by_name'));
    }
}
