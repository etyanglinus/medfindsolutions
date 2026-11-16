<?php

namespace Tests\Feature;

use App\Models\ProviderPaymentTransaction;
use App\Services\ProviderPaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;
use Tests\TestCase;

class PaystackCallbackFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('basic_payments')->insert([['key' => 'paystack_status', 'value' => 'active'], ['key' => 'paystack_secret_key', 'value' => 'test-secret']]);
        Http::preventStrayRequests();
        \Modules\Installer\app\Models\Configuration::create(['config' => 'setup_complete', 'value' => 1]);
        $this->withoutMiddleware(\App\Http\Middleware\DemoModeMiddleware::class);
    }

    private function supplier(): MedicalSupplier
    {
        return MedicalSupplier::create(['name' => 'Supplier', 'slug' => 'supplier', 'official_email' => 'supplier@example.test', 'password' => bcrypt('password'), 'registration_token' => 'test-token']);
    }

    public function test_initialize_sends_callback_and_cancel_urls_to_paystack(): void
    {
        $payment = app(ProviderPaystackService::class)->createAttempt($this->supplier(), 'supplier', 'premium');
        Http::fake(['api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/abc', 'access_code' => 'abc']])]);

        app(ProviderPaystackService::class)->initialize($payment);

        Http::assertSent(fn ($request) => $request['callback_url'] === route('provider.paystack.callback')
            && str_contains($request['metadata']['cancel_action'], 'cancelled=1')
            && str_contains($request['metadata']['cancel_action'], $payment->gateway_reference)
            && $request['metadata']['provider_type'] === 'supplier');
    }

    public function test_gateway_failure_returns_provider_to_plan_page_instead_of_error_page(): void
    {
        $this->supplier();
        Http::fake(['api.paystack.co/transaction/initialize' => Http::response(['status' => false, 'message' => 'Invalid key'], 401)]);

        $this->post(route('provider.paystack.start', ['type' => 'supplier', 'token' => 'test-token']), ['package' => 'premium'])
            ->assertRedirect(route('website.partner.subscription', ['type' => 'supplier', 'token' => 'test-token']))
            ->assertSessionHas('alert-type', 'error');
    }

    public function test_cancelled_checkout_returns_to_plan_page(): void
    {
        $payment = app(ProviderPaystackService::class)->createAttempt($this->supplier(), 'supplier', 'premium');

        $this->get(route('provider.paystack.callback', ['reference' => $payment->gateway_reference, 'cancelled' => 1]))
            ->assertRedirect(route('website.partner.subscription', ['type' => 'supplier', 'token' => 'test-token']))
            ->assertSessionHas('alert-type', 'warning');
        Http::assertNothingSent();
        $this->assertSame('initiated', $payment->fresh()->status);
    }

    public function test_unconfirmed_callback_redirects_with_message_and_unknown_reference_is_handled(): void
    {
        $payment = app(ProviderPaystackService::class)->createAttempt($this->supplier(), 'supplier', 'premium');
        Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'abandoned', 'reference' => $payment->gateway_reference]])]);

        $this->get(route('provider.paystack.callback', ['reference' => $payment->gateway_reference]))
            ->assertRedirect(route('website.partner.subscription', ['type' => 'supplier', 'token' => 'test-token']));
        $this->assertSame('initiated', $payment->fresh()->status);

        $this->get(route('provider.paystack.callback', ['reference' => 'MFS-does-not-exist']))->assertRedirect(route('login'));
        $this->get(route('provider.paystack.callback'))->assertRedirect(route('login'));
    }

    public function test_successful_callback_activates_and_redirects_to_login(): void
    {
        $payment = app(ProviderPaystackService::class)->createAttempt($this->supplier(), 'supplier', 'premium');
        Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'reference' => $payment->gateway_reference, 'amount' => 500000, 'currency' => 'KES', 'customer' => ['email' => 'supplier@example.test']]])]);

        $this->get(route('provider.paystack.callback', ['reference' => $payment->gateway_reference]))
            ->assertRedirect(route('login', ['type' => 'supplier']))
            ->assertSessionHas('alert-type', 'success');
        $this->assertSame('paid', ProviderPaymentTransaction::sole()->status);
    }

    public function test_callback_and_webhook_urls_are_exposed_for_paystack_dashboard(): void
    {
        $this->assertStringEndsWith('/provider/paystack/callback', ProviderPaystackService::callbackUrl());
        $this->assertStringEndsWith('/api/provider-payments/paystack/webhook', ProviderPaystackService::webhookUrl());
    }
}
