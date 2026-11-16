<?php

namespace Tests\Feature;

use App\Services\ProviderPaystackService;
use App\Http\Controllers\Provider\PaystackCheckoutController;
use App\Models\ProviderPaymentTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;
use Tests\TestCase;

class PaystackOnlyPolicyTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        DB::table('basic_payments')->insert([['key' => 'paystack_status', 'value' => 'active'], ['key' => 'paystack_secret_key', 'value' => 'test-secret']]);
        Http::preventStrayRequests();
    }
    private function client(): MedicalSupplier
    {
        return MedicalSupplier::create(['name' => 'Supplier', 'slug' => 'supplier', 'official_email' => 'supplier@example.test', 'password' => bcrypt('password'), 'registration_token' => 'test-token']);
    }
    public function test_checkout_ignores_client_amount_and_currency_and_uses_server_plan(): void
    {
        $this->client();
        Http::fake(['api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/test', 'access_code' => 'test']])]);
        $response = app(PaystackCheckoutController::class)->start(Request::create('/checkout', 'POST', ['package' => 'premium', 'amount' => 1, 'currency' => 'USD']), 'supplier', 'test-token');
        $this->assertSame('https://checkout.paystack.com/test', $response->getTargetUrl());
        $payment = ProviderPaymentTransaction::sole();
        $this->assertSame('paystack', $payment->gateway);
        $this->assertEquals(5000, $payment->amount);
        Http::assertSent(fn ($request) => $request['amount'] === 500000 && $request['currency'] === 'KES' && $request['email'] === 'supplier@example.test');
    }
    public function test_disabled_paystack_does_not_create_payment_attempts(): void
    {
        $client = $this->client();
        DB::table('basic_payments')->where('key', 'paystack_status')->update(['value' => 'inactive']);
        try { app(ProviderPaystackService::class)->createAttempt($client, 'supplier', 'premium'); $this->fail('Disabled Paystack must not initialize payments.'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) { $this->assertSame(503, $exception->getStatusCode()); }
        $this->assertDatabaseCount('provider_payment_transactions', 0);
        Http::assertNothingSent();
    }
    public function test_callback_cannot_confirm_a_different_reference(): void
    {
        $payment = app(ProviderPaystackService::class)->createAttempt($this->client(), 'supplier', 'premium');
        Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'reference' => 'SOMEONE-ELSE', 'amount' => 500000, 'currency' => 'KES', 'customer' => ['email' => 'supplier@example.test']]])]);
        try { app(ProviderPaystackService::class)->verify($payment->gateway_reference); $this->fail('A different reference must not be accepted.'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) { $this->assertSame(422, $exception->getStatusCode()); }
        $this->assertSame('initiated', $payment->fresh()->status);
        $this->assertSame('pending', $payment->subscription->status);
    }
    public function test_retired_gateways_cannot_be_used_directly(): void
    {
        $service = new \Modules\BasicPayment\app\Services\PaymentMethodService;
        $this->assertSame(['paystack'], $service->getSupportedPayments());
        $this->assertFalse($service->isSupportedGateway('bank'));
        $this->assertNull($service->getValue('paypal'));
        $request = Request::create('/pay-via-bank', 'POST');
        $route = (new Route('POST', 'pay-via-bank', ['uses' => 'Modules\BasicPayment\app\Http\Controllers\PaymentController@pay_via_bank']))->name('pay-via-bank');
        $route->bind($request); $request->setRouteResolver(fn () => $route);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(\App\Http\Middleware\EnforcePaystackOnly::class)->handle($request, fn () => response('Not allowed'));
    }
    public function test_late_payment_on_a_deleted_account_is_recorded_without_reactivation(): void
    {
        $client = $this->client();
        $payment = app(ProviderPaystackService::class)->createAttempt($client, 'supplier', 'premium');
        app(\App\Services\ClientDeletionService::class)->delete($client, 'supplier');
        app(ProviderPaystackService::class)->settle(['reference' => $payment->gateway_reference, 'status' => 'success', 'amount' => 500000, 'currency' => 'KES', 'customer' => ['email' => 'supplier@example.test']]);
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('cancelled', $payment->subscription->fresh()->status);
        $this->assertSoftDeleted($client);
    }
}
