<?php

namespace Tests\Feature;

use App\Models\ProviderPaymentTransaction;
use App\Models\ProviderProfile;
use App\Models\ProviderSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;
use Tests\TestCase;

class ProviderPaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_paystack_success_activates_once_and_duplicate_callback_is_idempotent(): void
    {
        $secret = 'local-test-paystack-secret';
        DB::table('basic_payments')->insert(['key' => 'paystack_secret_key', 'value' => $secret, 'created_at' => now(), 'updated_at' => now()]);
        [$profile, $subscription, $transaction, $supplier] = $this->createAttempt();
        $payload = ['event' => 'charge.success', 'data' => ['reference' => 'MFS-REF-001', 'status' => 'success', 'amount' => 500000, 'currency' => 'KES', 'customer' => ['email' => $supplier->official_email]]];
        $signature = hash_hmac('sha512', json_encode($payload), $secret);

        $this->withHeader('x-paystack-signature', $signature)->postJson('/api/provider-payments/paystack/webhook', $payload)->assertOk();
        $this->assertSame(ProviderPaymentTransaction::STATUS_PAID, $transaction->fresh()->status);
        $this->assertSame(ProviderSubscription::STATUS_APPROVED, $subscription->fresh()->status);
        $this->assertSame(1, $profile->subscriptions()->count());

        $this->withHeader('x-paystack-signature', $signature)->postJson('/api/provider-payments/paystack/webhook', $payload)->assertOk()->assertJson(['duplicate' => true]);
        $this->assertSame(1, $profile->subscriptions()->count());
    }

    public function test_invalid_paystack_signature_cannot_change_a_pending_attempt(): void
    {
        DB::table('basic_payments')->insert(['key' => 'paystack_secret_key', 'value' => 'local-test-paystack-secret', 'created_at' => now(), 'updated_at' => now()]);
        [, $subscription, $transaction] = $this->createAttempt();
        $payload = ['event' => 'charge.success', 'data' => ['reference' => 'MFS-REF-001', 'status' => 'success', 'amount' => 500000, 'currency' => 'KES']];

        $this->withHeader('x-paystack-signature', 'invalid')->postJson('/api/provider-payments/paystack/webhook', $payload)->assertUnauthorized();
        $this->assertSame(ProviderPaymentTransaction::STATUS_INITIATED, $transaction->fresh()->status);
        $this->assertSame(ProviderSubscription::STATUS_PENDING, $subscription->fresh()->status);
    }

    private function createAttempt(): array
    {
        $profile = ProviderProfile::create(['provider_type' => ProviderProfile::TYPE_SUPPLIER, 'provider_id' => 1500000001, 'status' => ProviderProfile::STATUS_PENDING_PAYMENT]);
        $supplier = MedicalSupplier::create(['provider_profile_id' => $profile->id, 'name' => 'Test Supplier', 'slug' => 'test-paystack-supplier', 'official_email' => 'supplier@example.test', 'phone' => '0700000000', 'password' => bcrypt('password')]);
        $profile->update(['provider_id' => $supplier->id]);
        $subscription = ProviderSubscription::create(['provider_profile_id' => $profile->id, 'plan_code' => 'premium', 'package_name' => 'Premium', 'amount' => 5000, 'currency' => 'KES', 'duration_months' => 3, 'status' => ProviderSubscription::STATUS_PENDING, 'metadata' => ['entitlements' => ['search_visibility' => true]]]);
        $transaction = ProviderPaymentTransaction::create(['provider_profile_id' => $profile->id, 'provider_subscription_id' => $subscription->id, 'gateway' => 'paystack', 'gateway_reference' => 'MFS-REF-001', 'idempotency_key' => 'paystack:MFS-REF-001', 'amount' => 5000, 'currency' => 'KES', 'status' => ProviderPaymentTransaction::STATUS_INITIATED, 'gateway_payload' => ['expected_email' => 'supplier@example.test', 'expected_amount_kobo' => 500000]]);
        return [$profile, $subscription, $transaction, $supplier];
    }
}
