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

    public function test_expired_checkout_is_closed_and_next_attempt_gets_a_fresh_reference(): void
    {
        [$clinic, $payment] = $this->stuckClinicPayment();
        $this->travel(2)->hours();
        $this->paystackSays('abandoned', $payment);

        $this->artisan('paystack:reconcile')->assertSuccessful();

        $payment->refresh();
        $this->assertSame('failed', $payment->status);
        $this->assertStringContainsString('abandoned', $payment->failure_reason);
        $this->assertSame('cancelled', $payment->subscription->fresh()->status);

        // Paying again creates a new attempt instead of reusing the expired Paystack page.
        $next = app(ProviderPaystackService::class)->createAttempt($clinic, 'clinic', 'premium');
        $this->assertNotSame($payment->gateway_reference, $next->gateway_reference);
        $this->assertSame('initiated', $next->status);
    }

    public function test_recent_abandoned_checkout_is_kept_open_while_the_payer_may_still_be_paying(): void
    {
        [, $payment] = $this->stuckClinicPayment();
        $this->paystackSays('abandoned', $payment);

        $this->artisan('paystack:reconcile');

        $this->assertSame('initiated', $payment->fresh()->status);
    }

    public function test_stale_attempt_is_rechecked_on_retry_and_expired_one_is_replaced(): void
    {
        [$clinic, $payment] = $this->stuckClinicPayment();
        $this->travel(2)->hours();
        $this->paystackSays('abandoned', $payment);

        $next = app(ProviderPaystackService::class)->createAttempt($clinic, 'clinic', 'premium');

        $this->assertNotSame($payment->id, $next->id);
        $this->assertSame('failed', $payment->fresh()->status);
    }

    public function test_money_arriving_after_an_attempt_was_closed_is_still_accepted(): void
    {
        [$clinic, $payment] = $this->stuckClinicPayment();
        $this->travel(2)->hours();
        $this->paystackSays('abandoned', $payment);
        $this->artisan('paystack:reconcile');
        $this->assertSame('failed', $payment->fresh()->status);

        app(ProviderPaystackService::class)->settle(['reference' => $payment->gateway_reference, 'status' => 'success',
            'amount' => (int) round($payment->amount * 100), 'currency' => 'KES', 'customer' => ['email' => 'payer@clinic.test']]);

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('approved', $payment->subscription->fresh()->status);
        $this->assertSame(1, (int) $clinic->fresh()->status);
    }
}
