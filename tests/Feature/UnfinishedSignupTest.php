<?php

namespace Tests\Feature;

use App\Http\Middleware\DemoModeMiddleware;
use App\Models\ProviderPaymentTransaction;
use App\Models\ProviderProfile;
use App\Services\ProviderPaystackService;
use App\Services\UnfinishedSignupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Modules\Doctor\app\Models\Clinic;
use Modules\Installer\app\Models\Configuration;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;
use Tests\TestCase;

class UnfinishedSignupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Storage::fake('public'); // sign-up logos
        config(['app.app_mode' => 'LIVE']);
        $this->withoutMiddleware(DemoModeMiddleware::class);
        Configuration::create(['config' => 'setup_complete', 'value' => 1]);
        Cache::put('setup_complete_status', true);
        Cache::put('setting', (object) ['maintenance_mode' => false, 'timezone' => 'Africa/Nairobi']);
        DB::table('basic_payments')->insert([['key' => 'paystack_status', 'value' => 'active'], ['key' => 'paystack_secret_key', 'value' => 'test-secret']]);
        Http::preventStrayRequests();
        Mail::fake();
    }

    private function registerSupplier(string $email = 'retry@supplier.test', string $name = 'Retry Supplier')
    {
        return $this->post(route('website.partner.register.store', 'supplier'), ['name' => $name, 'email' => $email, 'phone' => '0700000000', 'password' => 'password', 'password_confirmation' => 'password', 'logo' => \Illuminate\Http\UploadedFile::fake()->image('logo.png', 300, 270)]);
    }

    private function clinic(array $attributes = []): Clinic
    {
        return Clinic::create($attributes + ['name' => 'Magram Healthcare', 'slug' => 'magram-healthcare', 'email' => 'babu@clinic.test', 'phone' => '0700', 'password' => bcrypt('x'), 'status' => 0, 'registration_token' => 'old-token']);
    }

    private function clear(string $type, string $email): ?string
    {
        try { app(UnfinishedSignupService::class)->clearForRegistration($type, $email); return null; }
        catch (ValidationException $e) { return $e->errors()['email'][0]; }
    }

    public function test_supplier_can_sign_up_again_after_an_unfinished_sign_up(): void
    {
        $this->registerSupplier()->assertSessionHasNoErrors();
        $first = MedicalSupplier::where('official_email', 'retry@supplier.test')->sole();

        $this->registerSupplier('RETRY@supplier.test', 'Retry Supplier Ltd')->assertSessionHasNoErrors();

        $current = MedicalSupplier::whereRaw('lower(official_email) = ?', ['retry@supplier.test'])->sole();
        $this->assertNotSame($first->id, $current->id);
        $this->assertSame('Retry Supplier Ltd', $current->name);
        $old = MedicalSupplier::withTrashed()->find($first->id);
        $this->assertTrue($old->trashed());
        $this->assertStringStartsWith('retry@supplier.test#released-', $old->official_email);
        $this->assertNull($old->registration_token);
    }

    public function test_unfinished_clinic_with_an_expired_checkout_is_released_and_slug_is_freed(): void
    {
        $clinic = $this->clinic();
        $payment = app(ProviderPaystackService::class)->createAttempt($clinic, 'clinic', 'premium');
        $this->travel(2)->hours();
        Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'abandoned', 'reference' => $payment->gateway_reference, 'gateway_response' => 'Transaction expired']])]);

        $this->assertNull($this->clear('clinic', 'babu@clinic.test'));

        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertTrue($clinic->fresh()->trashed());
        // The same clinic name can be registered again without a duplicate-slug crash.
        $this->assertFalse(Clinic::withTrashed()->whereSlug('magram-healthcare')->exists());
        $this->assertNotNull(Clinic::create(['name' => 'Magram Healthcare', 'slug' => 'magram-healthcare', 'email' => 'babu@clinic.test', 'phone' => '0700', 'password' => 'x']));
    }

    public function test_payment_still_in_progress_blocks_release(): void
    {
        $clinic = $this->clinic();
        $payment = app(ProviderPaystackService::class)->createAttempt($clinic, 'clinic', 'premium');
        Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'ongoing', 'reference' => $payment->gateway_reference]])]);

        $this->assertStringContainsString('still being processed', $this->clear('clinic', 'babu@clinic.test'));
        $this->assertFalse($clinic->fresh()->trashed());
        $this->assertSame('initiated', $payment->fresh()->status);
    }

    public function test_paid_or_active_accounts_are_protected_with_a_helpful_message(): void
    {
        $this->clinic(['status' => 1]);
        $this->assertStringContainsString('Forgot password', $this->clear('clinic', 'babu@clinic.test'));

        $this->registerSupplier()->assertSessionHasNoErrors();
        $supplier = MedicalSupplier::where('official_email', 'retry@supplier.test')->sole();
        ProviderPaymentTransaction::create(['provider_profile_id' => $supplier->provider_profile_id, 'gateway' => 'paystack', 'gateway_reference' => 'PAID-1', 'idempotency_key' => 'PAID-1', 'amount' => 5000, 'currency' => 'KES', 'status' => 'paid', 'paid_at' => now()]);
        $this->registerSupplier()->assertSessionHasErrors('email');
        $this->assertFalse($supplier->fresh()->trashed());
    }

    public function test_account_deleted_by_admin_frees_its_email(): void
    {
        $clinic = $this->clinic(['status' => 1]);
        app(\App\Services\ClientDeletionService::class)->delete($clinic, 'clinic');

        $this->assertNull($this->clear('clinic', 'babu@clinic.test'));
        $this->assertStringContainsString('#released-', Clinic::withTrashed()->find($clinic->id)->email);
    }

    public function test_account_prepared_by_admin_is_not_replaced_by_a_stranger(): void
    {
        $clinic = $this->clinic();
        $profile = ProviderProfile::create(['provider_type' => 'clinic', 'provider_id' => $clinic->id, 'status' => 'pending_payment']);
        $profile->subscriptions()->create(['plan_code' => 'premium', 'package_name' => 'Premium', 'amount' => 5000, 'currency' => 'KES', 'duration_months' => 3, 'status' => 'pending', 'metadata' => ['admin_onboarding' => true]]);

        $this->assertStringContainsString('already set up an account', $this->clear('clinic', 'babu@clinic.test'));
        $this->assertFalse($clinic->fresh()->trashed());
    }
}
