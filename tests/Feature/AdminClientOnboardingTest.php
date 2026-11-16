<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ClientOnboardingController;
use App\Jobs\ActivateAdminClient;
use App\Mail\ClientAccountActivated;
use App\Models\Admin;
use App\Models\ProviderPaymentTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Modules\Doctor\app\Services\SubscriptionPlanService;
use Tests\TestCase;

class AdminClientOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\DB::table('basic_payments')->updateOrInsert(['key' => 'paystack_status'], ['value' => 'active']);
        \Illuminate\Support\Facades\DB::table('basic_payments')->updateOrInsert(['key' => 'paystack_secret_key'], ['value' => 'test-secret']);
        \Illuminate\Support\Facades\Http::fake(['api.paystack.co/transaction/initialize' => \Illuminate\Support\Facades\Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/test', 'access_code' => 'test-code']])]);
    }

    private function createClient(string $type): ProviderPaymentTransaction
    {
        $admin = new Admin;
        $admin->forceFill(['name' => 'Owner', 'email' => 'owner-'.$type.'@example.test', 'password' => bcrypt('password'), 'status' => 'active', 'is_super_admin' => 1])->save();
        $this->actingAs($admin, 'admin');
        $plans = app(SubscriptionPlanService::class);
        $request = Request::create('/admin/clients', 'POST', ['type' => $type, 'name' => 'Test Client', 'email' => $type.'@example.test', 'phone' => '0700000000', 'package' => $plans->for($type)[0]['id']]);
        if ($type === 'doctor') {
            $request->merge([
                'department_id' => \Modules\Doctor\app\Models\Department::create(['slug' => 'test-department'])->id,
                'location_id' => \Modules\Doctor\app\Models\Location::create(['status' => 1])->id,
            ]);
        }
        app(ClientOnboardingController::class)->store($request, $plans);
        return ProviderPaymentTransaction::latest('id')->firstOrFail();
    }

    public function test_unpaid_client_stays_inactive_and_receives_no_login_email(): void
    {
        Mail::fake();
        $payment = $this->createClient('supplier');
        (new ActivateAdminClient($payment->id))->handle();
        $this->assertNull($payment->providerProfile->provider->email_verified_at);
        $this->assertSame('pending_payment', $payment->providerProfile->status);
        $this->assertSame('paystack', $payment->gateway);
        Mail::assertQueued(\App\Mail\ClientPaymentInvitation::class);
        Mail::assertNothingSent();
    }

    public function test_confirmed_payment_activates_each_partner_once_and_removes_stored_credentials(): void
    {
        Mail::fake();
        foreach (['school', 'supplier', 'clinic', 'doctor'] as $type) {
            $payment = $this->createClient($type);
            $payment->subscription->update(['status' => 'approved', 'starts_at' => now(), 'expires_at' => now()->addMonths(3)]);
            $payment->update(['status' => 'paid', 'paid_at' => now()]);
            (new ActivateAdminClient($payment->id))->handle();
            (new ActivateAdminClient($payment->id))->handle();
            $client = $payment->providerProfile->provider->fresh();
            $this->assertNotNull($client->email_verified_at);
            $this->assertSame('approved', $payment->providerProfile->fresh()->status);
            $this->assertArrayNotHasKey('login_secret', $payment->subscription->fresh()->metadata);
            Mail::assertSent(ClientAccountActivated::class, fn ($mail) => $mail->loginEmail === $type.'@example.test' && Hash::check($mail->loginPassword, $client->password));
            if (in_array($type, ['clinic', 'doctor'])) $this->assertSame(1, $client->subscriptions()->where('status', 'approved')->count());
        }
        Mail::assertSentCount(4);
    }

    public function test_only_verified_paystack_payment_grants_access(): void
    {
        Mail::fake();
        $payment = $this->createClient('supplier');
        $service = app(\App\Services\ProviderPaystackService::class);
        $data = ['reference' => $payment->gateway_reference, 'status' => 'success', 'amount' => (int) round($payment->amount * 100), 'currency' => 'KES', 'customer' => ['email' => 'supplier@example.test']];
        $service->settle($data);
        (new ActivateAdminClient($payment->id))->handle();
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('approved', $payment->subscription->fresh()->status);
        Mail::assertSentCount(1);
    }
    public function test_employee_cannot_create_a_client_without_the_type_permission(): void
    {
        $admin = new Admin;
        $admin->forceFill(['name' => 'Employee', 'email' => 'employee@example.test', 'password' => bcrypt('password'), 'status' => 'active', 'is_super_admin' => 0])->save();
        $this->actingAs($admin, 'admin');
        $this->expectException(\App\Exceptions\AccessPermissionDeniedException::class);
        app(ClientOnboardingController::class)->store(Request::create('/admin/clients', 'POST', ['type' => 'supplier']), app(SubscriptionPlanService::class));
    }
}
