<?php

namespace Tests\Feature;

use App\Services\ProviderPaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Doctor\app\Models\Clinic;
use Tests\TestCase;

/** Reproduces a clinic that paid late (M-Pesa / bank transfer) after leaving the Paystack page. */
class PaystackReconcileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('basic_payments')->insert([['key' => 'paystack_status', 'value' => 'active'], ['key' => 'paystack_secret_key', 'value' => 'test-secret']]);
        Http::preventStrayRequests();
        \Illuminate\Support\Facades\Mail::fake();
        \Modules\Installer\app\Models\Configuration::create(['config' => 'setup_complete', 'value' => 1]);
        $this->withoutMiddleware(\App\Http\Middleware\DemoModeMiddleware::class);
    }

    private function stuckClinicPayment()
    {
        $clinic = Clinic::create(['name' => 'Late Payer Clinic', 'slug' => 'late-payer', 'email' => 'payer@clinic.test', 'phone' => '0700000000', 'password' => bcrypt('password'), 'status' => 0, 'registration_token' => 'clinic-token']);
        return [$clinic, app(ProviderPaystackService::class)->createAttempt($clinic, 'clinic', 'premium')];
    }

    private function paystackSays(string $status, $payment): void
    {
        Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => [
            'status' => $status, 'reference' => $payment->gateway_reference, 'amount' => (int) round($payment->amount * 100),
            'currency' => 'KES', 'channel' => 'mobile_money', 'gateway_response' => $status, 'customer' => ['email' => 'payer@clinic.test'],
        ]])]);
    }

    public function test_unfinished_payment_is_left_pending(): void
    {
        [$clinic, $payment] = $this->stuckClinicPayment();
        $this->paystackSays('ongoing', $payment);

        $this->artisan('paystack:reconcile')->assertSuccessful();

        $this->assertSame('initiated', $payment->fresh()->status);
        $this->assertSame('ongoing', data_get($payment->fresh()->gateway_payload, 'last_check.status'));
        $this->assertSame(0, (int) $clinic->fresh()->status);
        \Illuminate\Support\Facades\Mail::assertNothingSent();
    }

    public function test_late_successful_payment_activates_the_clinic_once(): void
    {
        [$clinic, $payment] = $this->stuckClinicPayment();
        $this->paystackSays('success', $payment);

        $this->artisan('paystack:reconcile')->assertSuccessful();
        $this->artisan('paystack:reconcile', ['reference' => $payment->gateway_reference])->assertSuccessful();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('approved', $payment->subscription->fresh()->status);
        $this->assertSame(1, (int) $clinic->fresh()->status);
        $this->assertSame(1, $clinic->subscriptions()->where('status', 'approved')->count());
        Http::assertSentCount(1); // second run sees it is already paid and does not call Paystack again
    }

    public function test_wrong_amount_is_not_activated(): void
    {
        [$clinic, $payment] = $this->stuckClinicPayment();
        Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => [
            'status' => 'success', 'reference' => $payment->gateway_reference, 'amount' => 100, 'currency' => 'KES', 'customer' => ['email' => 'payer@clinic.test'],
        ]])]);

        $this->artisan('paystack:reconcile');

        $this->assertSame('initiated', $payment->fresh()->status);
        $this->assertSame(0, (int) $clinic->fresh()->status);
    }

    public function test_confirmed_payment_emails_the_provider_once_with_a_working_access_link(): void
    {
        [$clinic, $payment] = $this->stuckClinicPayment();
        $this->paystackSays('success', $payment);

        $this->artisan('paystack:reconcile')->assertSuccessful();
        $this->artisan('paystack:reconcile')->assertSuccessful();

        $sent = null;
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ProviderPaymentConfirmed::class, function ($mail) use (&$sent) { $sent = $mail; return $mail->hasTo('payer@clinic.test'); });
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ProviderPaymentConfirmed::class, 1);
        $this->assertSame($payment->gateway_reference, $sent->reference);
        $this->assertNotEmpty(data_get($payment->subscription->fresh()->metadata, 'confirmation_email_sent_at'));

        $this->assertNull($clinic->fresh()->email_verified_at);
        $this->get($sent->accessUrl)->assertRedirect(route('login', ['type' => 'clinic']))->assertSessionHas('alert-type', 'success');
        $this->assertNotNull($clinic->fresh()->email_verified_at);

        // A tampered link (different account id) is rejected.
        $this->get(str_replace('/payment-access/'.$clinic->id, '/payment-access/999', $sent->accessUrl))->assertForbidden();
    }

    public function test_supplier_paying_through_callback_gets_email_and_can_then_log_in(): void
    {
        $supplier = \Modules\MedicalSupplier\app\Models\MedicalSupplier::create(['name' => 'Supplier', 'slug' => 'supplier', 'official_email' => 'payer@clinic.test', 'password' => bcrypt('secret-pass'), 'registration_token' => 'test-token']);
        $payment = app(ProviderPaystackService::class)->createAttempt($supplier, 'supplier', 'premium');
        $this->paystackSays('success', $payment);

        $this->get(route('provider.paystack.callback', ['reference' => $payment->gateway_reference]))->assertRedirect();

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ProviderPaymentConfirmed::class, function ($mail) use (&$sent) { $sent = $mail; return true; });
        $this->get($sent->accessUrl)->assertRedirect();
        $this->post(route('website.partner.login.store', ['type' => 'supplier']), ['email' => 'payer@clinic.test', 'password' => 'secret-pass'])
            ->assertRedirect(route('website.partner.dashboard', ['type' => 'supplier']));
    }
}
