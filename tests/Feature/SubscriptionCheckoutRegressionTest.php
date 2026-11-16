<?php

namespace Tests\Feature;

use App\Models\ProviderProfile;
use App\Models\ProviderSubscription;
use App\Models\SubscriptionCoupon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Modules\Doctor\app\Models\Doctor;
use Modules\Doctor\app\Models\DoctorSubscription;
use Modules\Doctor\app\Services\SubscriptionAccessService;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;
use Tests\TestCase;

class SubscriptionCheckoutRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'http://localhost']);
        \Illuminate\Support\Facades\URL::forceRootUrl('http://localhost');
        $this->withoutMiddleware();
        Mail::fake();
        Http::preventStrayRequests();
    }

    private function supplier(): MedicalSupplier
    {
        $profile = ProviderProfile::create(['provider_type' => 'supplier', 'provider_id' => 999, 'status' => ProviderProfile::STATUS_PENDING_PAYMENT]);
        $supplier = MedicalSupplier::create(['name' => 'Supplier', 'slug' => 'supplier', 'official_email' => 'supplier@example.test', 'phone' => '0700000000', 'password' => bcrypt('password'), 'registration_token' => 'test-token', 'provider_profile_id' => $profile->id]);
        $profile->update(['provider_id' => $supplier->id]);
        return $supplier;
    }

    private function coupon(array $attributes = []): SubscriptionCoupon
    {
        return SubscriptionCoupon::create($attributes + ['code' => 'SAVE25', 'provider_types' => ['supplier', 'doctor', 'clinic'], 'discount_type' => 'percent', 'discount_value' => 25, 'is_active' => true, 'usage_limit' => 5]);
    }

    public function test_quote_returns_discount_without_consuming_coupon_and_requires_account(): void
    {
        $this->supplier();
        $coupon = $this->coupon();
        $this->postJson(route('subscription.quote', 'supplier'), ['token' => 'test-token', 'package' => 'premium', 'coupon_code' => ' save25 '])
            ->assertOk()->assertJson(['original_price' => 5000, 'discount_amount' => 1250, 'price' => 3750]);
        $this->assertSame(0, $coupon->fresh()->usage_count);
        $this->postJson(route('subscription.quote', 'supplier'), ['package' => 'premium', 'coupon_code' => 'SAVE25'])->assertForbidden();
    }

    public function test_paystack_checkout_records_discount_and_reuses_pending_attempt(): void {
 $this->supplier(); $coupon = $this->coupon();
 DB::table("basic_payments")->updateOrInsert(["key" => "paystack_status"], ["value" => "active"]);
 DB::table("basic_payments")->updateOrInsert(["key" => "paystack_secret_key"], ["value" => "test-secret"]);
 Http::fake(["api.paystack.co/transaction/initialize" => Http::response(["status" => true, "data" => ["authorization_url" => "https://checkout.paystack.com/test", "access_code" => "code"]])]);
 $url = route("provider.paystack.start", ["type" => "supplier", "token" => "test-token"]);
 $this->post($url, ["package" => "premium", "coupon_code" => "SAVE25"])->assertRedirect("https://checkout.paystack.com/test");
 $this->post($url, ["package" => "premium", "coupon_code" => "SAVE25"])->assertRedirect("https://checkout.paystack.com/test");
 $subscription = ProviderSubscription::sole();
 $this->assertEquals(3750, $subscription->amount);
 $this->assertSame("SAVE25", $subscription->metadata["coupon_code"]);
 $this->assertTrue($subscription->metadata["entitlements"]["analytics"]);
 $this->assertSame("paystack", $subscription->payments()->sole()->gateway);
 $this->assertSame(1, $coupon->fresh()->usage_count);
}

    public function test_full_discount_activates_without_paystack(): void {
 $supplier = $this->supplier(); $coupon = $this->coupon(["discount_value" => 100]);
 DB::table("basic_payments")->updateOrInsert(["key" => "paystack_status"], ["value" => "active"]);
 DB::table("basic_payments")->updateOrInsert(["key" => "paystack_secret_key"], ["value" => "test-secret"]);
 \Illuminate\Support\Facades\Http::fake();
 $this->post(route("provider.paystack.start", ["type" => "supplier", "token" => "test-token"]), ["package" => "premium", "coupon_code" => "SAVE25"])
     ->assertRedirect()->assertSessionHas("alert-type", "success");
 \Illuminate\Support\Facades\Http::assertNothingSent();
 $payment = \App\Models\ProviderPaymentTransaction::sole();
 $this->assertSame(["coupon", "paid", 0.0], [$payment->gateway, $payment->status, (float) $payment->amount]);
 $subscription = ProviderSubscription::sole();
 $this->assertSame("approved", $subscription->status);
 $this->assertNotNull($subscription->expires_at);
 $this->assertSame(1, $coupon->fresh()->usage_count);
}

    public function test_invalid_coupon_cannot_create_payment(): void {
 $this->supplier();
 DB::table("basic_payments")->updateOrInsert(["key" => "paystack_status"], ["value" => "active"]);
 DB::table("basic_payments")->updateOrInsert(["key" => "paystack_secret_key"], ["value" => "test-secret"]);
 $this->postJson(route("provider.paystack.start", ["type" => "supplier", "token" => "test-token"]), ["package" => "premium", "coupon_code" => "NOTREAL"])->assertUnprocessable();
 $this->assertDatabaseCount("provider_subscriptions", 0);
}

    public function test_paystack_verifies_discounted_amount_and_rejects_wrong_amount(): void {
 $this->supplier(); $coupon = $this->coupon();
 DB::table("basic_payments")->updateOrInsert(["key" => "paystack_status"], ["value" => "active"]);
 DB::table("basic_payments")->updateOrInsert(["key" => "paystack_secret_key"], ["value" => "test-secret"]);
 Http::fake(["api.paystack.co/transaction/initialize" => Http::response(["status" => true, "data" => ["authorization_url" => "https://checkout.paystack.com/test", "access_code" => "code"]])]);
 $this->post(route("provider.paystack.start", ["type" => "supplier", "token" => "test-token"]), ["package" => "premium", "coupon_code" => "SAVE25"])->assertRedirect();
 $payment = \App\Models\ProviderPaymentTransaction::sole();
 Http::fake(["api.paystack.co/transaction/verify/*" => Http::sequence()
 ->push(["status" => true, "data" => ["status" => "success", "reference" => $payment->gateway_reference, "amount" => 500000, "currency" => "KES", "customer" => ["email" => "supplier@example.test"]]])
 ->push(["status" => true, "data" => ["status" => "success", "reference" => $payment->gateway_reference, "amount" => 375000, "currency" => "KES", "customer" => ["email" => "supplier@example.test"]]])]);
 $url = route("provider.paystack.callback", ["reference" => $payment->gateway_reference]);
 $this->get($url)->assertRedirect()->assertSessionHas("alert-type", "warning");
 $this->assertSame("initiated", $payment->fresh()->status);
 $this->get($url)->assertRedirect(route("login", ["type" => "supplier"]));
 $this->assertSame("paid", $payment->fresh()->status);
 $this->assertEquals(3750, ProviderSubscription::sole()->amount);
 $this->assertSame(1, $coupon->fresh()->usage_count);
}

    public function test_legacy_plan_entitlements_are_restored_without_overriding_explicit_permissions(): void
    {
        $subscription = new DoctorSubscription(['package_name' => 'Premium', 'entitlements' => null]);
        $this->assertTrue($subscription->hasEntitlement('analytics'));
        $this->assertFalse($subscription->hasEntitlement('insurance'));
        $subscription->entitlements = ['analytics' => false];
        $this->assertFalse($subscription->hasEntitlement('analytics'));
        $this->assertFalse($subscription->hasEntitlement('whatsapp'));
        $subscription->package_name = 'Unknown';
        $subscription->entitlements = null;
        $this->assertFalse($subscription->hasEntitlement('analytics'));
    }
}
