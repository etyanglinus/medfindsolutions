<?php

namespace Tests\Feature;

use App\Models\ProviderProfile;
use App\Mail\PartnerEmailVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Http\Middleware\DemoModeMiddleware;
use Modules\Installer\app\Models\Configuration;
use Modules\HealthcareInstitution\app\Models\HealthcareInstitution;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;
use Tests\TestCase;

class PartnerAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Storage::fake('public'); // sign-up logos
        config(['app.app_mode' => 'LIVE']);
        config(['app.url' => 'http://localhost']);
        \Illuminate\Support\Facades\URL::forceRootUrl('http://localhost');
        $this->withoutMiddleware(DemoModeMiddleware::class);
        Configuration::create(['config' => 'setup_complete', 'value' => 1]);
        Cache::put('setup_complete_status', true);
        Cache::put('setting', (object) ['maintenance_mode' => false, 'timezone' => 'Africa/Nairobi']);
    }

    public function test_school_registration_creates_profile_and_sends_verification_email(): void
    {
        Mail::fake();

        $response = $this->post(route('website.partner.register.store', 'school'), $this->registrationData('school@example.test'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('healthcare_institutions', ['official_email' => 'school@example.test']);

        $record = HealthcareInstitution::where('official_email', 'school@example.test')->firstOrFail();
        $this->assertDatabaseHas('provider_profiles', [
            'provider_type' => ProviderProfile::TYPE_SCHOOL,
            'provider_id' => $record->id,
            'status' => ProviderProfile::STATUS_PENDING_PAYMENT,
        ]);
        $this->assertNotEmpty($record->email_verified_token);
        Mail::assertSent(PartnerEmailVerification::class, fn ($mail) => $mail->hasTo('school@example.test'));
        $response->assertRedirect(route('website.partner.subscription', ['type' => 'school', 'token' => $record->registration_token]));
    }

    public function test_supplier_registration_creates_provider_profile(): void
    {
        $this->post(route('website.partner.register.store', 'supplier'), $this->registrationData('supplier@example.test'));

        $record = MedicalSupplier::where('official_email', 'supplier@example.test')->firstOrFail();
        $this->assertDatabaseHas('provider_profiles', [
            'provider_type' => ProviderProfile::TYPE_SUPPLIER,
            'provider_id' => $record->id,
        ]);
    }

    public function test_unverified_school_cannot_login_then_signed_verification_allows_login(): void
    {
        $school = $this->createSchool('school@example.test', ['email_verified_token' => 'valid-token']);

        $this->post(route('website.partner.login.store', 'school'), ['email' => $school->official_email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest('school');

        $verifyUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('website.partner.verification.verify', now()->addMinutes(60), ['type' => 'school', 'token' => 'valid-token']);
        $this->get($verifyUrl)->assertRedirect(route('website.partner.login', 'school'));
        $this->assertNotNull($school->fresh()->email_verified_at);

        $this->post(route('website.partner.login.store', 'school'), ['email' => $school->official_email, 'password' => 'password'])
            ->assertRedirect(route('website.partner.dashboard', 'school'));
        $this->assertAuthenticatedAs($school->fresh(), 'school');
    }

    public function test_supplier_guard_cannot_access_school_dashboard(): void
    {
        $supplier = $this->createSupplier('supplier@example.test', ['email_verified_at' => now()]);

        $this->actingAs($supplier, 'supplier')->get(route('website.partner.dashboard', 'school'))->assertForbidden();
    }

    private function registrationData(string $email): array
    {
        // A logo is compulsory at sign-up, so every listing has one from day one.
        return ['name' => 'Example Partner', 'email' => $email, 'phone' => '0700000000', 'password' => 'password', 'password_confirmation' => 'password', 'logo' => \Illuminate\Http\UploadedFile::fake()->image('logo.png', 300, 270)];
    }

    private function createSchool(string $email, array $attributes = []): HealthcareInstitution
    {
        $profile = ProviderProfile::create(['provider_type' => ProviderProfile::TYPE_SCHOOL, 'provider_id' => 999, 'status' => ProviderProfile::STATUS_PENDING_PAYMENT]);
        $school = HealthcareInstitution::create($attributes + ['provider_profile_id' => $profile->id, 'name' => 'Example School', 'slug' => 'example-school', 'official_email' => $email, 'phone' => '0700000000', 'password' => bcrypt('password')]);
        $profile->update(['provider_id' => $school->id]);
        return $school;
    }

    private function createSupplier(string $email, array $attributes = []): MedicalSupplier
    {
        $profile = ProviderProfile::create(['provider_type' => ProviderProfile::TYPE_SUPPLIER, 'provider_id' => 999, 'status' => ProviderProfile::STATUS_PENDING_PAYMENT]);
        $supplier = MedicalSupplier::create($attributes + ['provider_profile_id' => $profile->id, 'name' => 'Example Supplier', 'slug' => 'example-supplier', 'official_email' => $email, 'phone' => '0700000000', 'password' => bcrypt('password')]);
        $profile->update(['provider_id' => $supplier->id]);
        return $supplier;
    }
}
