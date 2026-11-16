<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Doctor\app\Models\Doctor;
use Modules\Doctor\app\Services\SubscriptionAccessService;
use Tests\TestCase;

class DoctorPortalRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'http://localhost']);
        \Illuminate\Support\Facades\URL::forceRootUrl('http://localhost');
        $this->withoutMiddleware();
        Mail::fake();
    }

    private function doctor(): Doctor
    {
        $department = DB::table('departments')->insertGetId(['slug' => 'test-department']);
        $location = DB::table('locations')->insertGetId(['status' => 1]);
        return Doctor::create(['department_id' => $department, 'location_id' => $location, 'name' => 'Test Doctor', 'slug' => 'test-doctor', 'email' => 'doctor@example.test', 'phone' => '0700000000', 'password' => bcrypt('password'), 'registration_token' => 'doctor-token']);
    }

    #[\PHPUnit\Framework\Attributes\Group('known-failing')] // pre-existing failure, see tests/KNOWN_FAILING.md
    public function test_doctor_free_coupon_needs_no_payment_reference(): void
    {
        $doctor = $this->doctor();
        $coupon = \App\Models\SubscriptionCoupon::create(['code' => 'FREE', 'provider_types' => ['doctor'], 'discount_type' => 'percent', 'discount_value' => 100, 'is_active' => true]);
        $this->post(route('doctor.subscription.store', 'doctor-token'), ['package' => 'premium', 'coupon_code' => 'FREE'])->assertSessionHasNoErrors()->assertRedirect();
        $subscription = $doctor->subscriptions()->sole();
        $this->assertEquals(0, $subscription->package_price);
        $this->assertTrue($subscription->hasEntitlement('analytics'));
        $this->assertSame('pending', $subscription->status);
        $this->assertSame(1, $coupon->fresh()->usage_count);
    }

    public function test_latest_active_plan_wins_and_inbox_uses_correct_layout_section(): void
    {
        $doctor = $this->doctor();
        $base = ['package_name' => 'Basic', 'plan_code' => 'basic', 'package_price' => 1000, 'plan_duration' => 1, 'payment_method' => 'bank_transfer', 'status' => 'approved', 'approved_at' => now()->subDays(2), 'expires_at' => now()->addYear(), 'transaction_code' => 'OLD'];
        $doctor->subscriptions()->create($base);
        $premium = $doctor->subscriptions()->create(array_merge($base, ['package_name' => 'Premium', 'plan_code' => 'premium', 'approved_at' => now()->subDay(), 'expires_at' => now()->addMonth(), 'transaction_code' => 'NEW']));
        $doctor->subscriptions()->create(array_merge($base, ['approved_at' => now()->addDay(), 'transaction_code' => 'FUTURE']));
        $this->assertSame($premium->id, app(SubscriptionAccessService::class)->activeDoctorSubscription($doctor)->id);
        $this->actingAs($doctor, 'doctor');
        $view = app(\App\Http\Controllers\ProviderInboxController::class)->index('doctor');
        $this->assertSame('admin-content', $view->getData()['contentSection']);
        $view = app(\App\Http\Controllers\ProviderDocumentController::class)->index('doctor');
        $this->assertSame('admin-content', $view->getData()['contentSection']);
    }

    public function test_verification_can_be_resent_without_portal_login(): void
    {
        $doctor = $this->doctor();
        $this->post(route('doctor.registration.verification.resend', 'doctor-token'))->assertRedirect();
        Mail::assertSent(\App\Mail\GlobalMail::class, fn ($mail) => $mail->hasTo($doctor->email));
        $this->assertNotEmpty($doctor->fresh()->email_verified_token);
    }

    public function test_smtp_fallback_preserves_cached_configuration(): void
    {
        config(['mail.mailers.smtp.host' => 'smtp.example.test', 'mail.mailers.smtp.username' => 'configured-user', 'mail.mailers.smtp.password' => 'test-password', 'mail.from.address' => 'sender@example.test']);
        \Illuminate\Support\Facades\Cache::put('setting', (object) ['timezone' => 'Africa/Nairobi', 'mail_host' => 'sandbox.smtp.mailtrap.io', 'mail_username' => 'mail_username', 'mail_password' => 'mail_password']);
        (new \App\Providers\AppServiceProvider($this->app))->boot();
        $this->assertSame('smtp.example.test', config('mail.mailers.smtp.host'));
        $this->assertSame('configured-user', config('mail.mailers.smtp.username'));
        $this->assertSame('sender@example.test', config('mail.from.address'));
    }
}
