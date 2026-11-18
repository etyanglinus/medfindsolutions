<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ProviderProfileController;
use App\Mail\LicenceExpiryNotice;
use App\Models\Admin;
use App\Models\ProviderProfile;
use App\Models\ProviderSubscription;
use App\Services\LicenceVerification;
use App\Services\PublicDirectory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Doctor\app\Models\Doctor;
use Modules\Doctor\app\Models\ProviderDocument;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;
use Tests\TestCase;

class ListingVerificationTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\CompletesProviderProfiles;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        DB::table('settings')->updateOrInsert(['key' => 'require_verified_listing'], ['value' => '0']);
        Cache::forever('setting', (object) DB::table('settings')->pluck('value', 'key')->all());
    }

    private function doctor(): Doctor
    {
        $department = DB::table('departments')->insertGetId(['slug' => 'cardiologist', 'status' => 1]);
        $location = DB::table('locations')->insertGetId(['status' => 1]);
        $doctor = Doctor::create(['department_id' => $department, 'location_id' => $location, 'name' => 'Dr Licence', 'slug' => 'dr-licence', 'email' => 'dr@example.test', 'phone' => '0700', 'password' => 'x', 'status' => 1, 'email_verified_at' => now()]);
        $this->completeDoctor($doctor);
        $doctor->subscriptions()->create(['package_name' => 'Basic', 'plan_code' => 'basic', 'package_price' => 1000, 'plan_duration' => 1, 'payment_method' => 'paystack', 'status' => 'approved', 'approved_at' => now()->subDay(), 'expires_at' => now()->addMonth(), 'transaction_code' => 'T1', 'entitlements' => ['search_visibility' => true]]);
        return $doctor;
    }

    private function licence(string $type, int $id, string $status, ?string $expires): ProviderDocument
    {
        return ProviderDocument::create(['provider_type' => $type, 'provider_id' => $id, 'licensing_body' => 'KMPDC', 'verification_body' => 'KMPDC', 'registration_number' => 'R1', 'accreditation_expires_at' => $expires, 'file_path' => 'docs/x.pdf', 'status' => $status]);
    }

    private function listed(): array
    {
        PublicDirectory::forget();
        return app(PublicDirectory::class)->doctors()->pluck('slug')->all();
    }

    public function test_switch_controls_whether_unverified_specialists_are_listed(): void
    {
        $doctor = $this->doctor();
        $this->assertSame(['dr-licence'], $this->listed(), 'Off by default: nothing disappears on deploy.');

        app(LicenceVerification::class)->setRequired(true);
        $this->assertSame([], $this->listed());

        $this->licence('doctor', $doctor->id, 'pending', null);
        $this->assertSame([], $this->listed(), 'A pending document is not enough.');

        $doc = $this->licence('doctor', $doctor->id, 'approved', now()->addYear()->toDateString());
        $this->assertSame(['dr-licence'], $this->listed());

        $doc->update(['accreditation_expires_at' => now()->subDay()->toDateString()]);
        $this->assertSame([], $this->listed(), 'An expired licence no longer counts.');
    }

    public function test_daily_check_warns_then_expires_and_unpublishes_a_supplier(): void
    {
        $supplier = MedicalSupplier::create(['name' => 'Supplier', 'slug' => 'supplier', 'official_email' => 'sup@example.test', 'password' => 'x']);
        $profile = ProviderProfile::create(['provider_type' => 'supplier', 'provider_id' => $supplier->id, 'status' => 'published', 'published_at' => now(), 'verification_status' => 'verified', 'verified_at' => now()]);
        $supplier->update(['provider_profile_id' => $profile->id]);
        $doc = $this->licence('supplier', $supplier->id, 'approved', today()->addDays(14)->toDateString());

        $this->artisan('providers:check-licences')->assertSuccessful();
        Mail::assertSent(LicenceExpiryNotice::class, fn ($m) => $m->hasTo('sup@example.test') && !$m->expired);

        $this->travel(15)->days();
        $this->artisan('providers:check-licences')->assertSuccessful();

        $this->assertSame('expired', $doc->fresh()->status);
        $profile->refresh();
        $this->assertSame('approved', $profile->status);
        $this->assertNull($profile->published_at);
        $this->assertSame('unverified', $profile->verification_status);
        Mail::assertSent(LicenceExpiryNotice::class, fn ($m) => $m->expired && $m->listingHidden);
    }

    public function test_publishing_a_school_or_supplier_requires_verification_and_a_valid_licence(): void
    {
        $admin = new Admin;
        $admin->forceFill(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => bcrypt('x'), 'status' => 'active', 'is_super_admin' => 1])->save();
        $this->actingAs($admin, 'admin');
        $supplier = MedicalSupplier::create(['name' => 'Supplier', 'slug' => 'supplier', 'official_email' => 'sup@example.test', 'password' => 'x', 'description' => 'We offer friendly, professional care for patients and families, with clear advice and fair, transparent prices.', 'address' => 'Nairobi', 'logo' => 'logo.png', 'phone' => '0700']);
        $profile = ProviderProfile::create(['provider_type' => 'supplier', 'provider_id' => $supplier->id, 'status' => 'approved']);
        $supplier->update(['provider_profile_id' => $profile->id]);
        $this->licence('supplier', $supplier->id, 'pending', null);
        $controller = app(ProviderProfileController::class);

        // Refusals come back as a readable message on the same page, never an error page.
        $this->assertSame('error', $controller->verify($profile)->getSession()->get('alert-type'), 'Cannot verify without an approved licence.');
        $this->assertSame('error', $controller->publish($profile)->getSession()->get('alert-type'), 'Cannot publish without an approved licence.');
        $this->assertSame('approved', $profile->fresh()->status);
        $this->assertSame('unverified', $profile->fresh()->verification_status);

        // Once the licence is approved, one Publish click verifies and publishes.
        $this->licence('supplier', $supplier->id, 'approved', null);
        $controller->publish($profile->fresh());
        $this->assertSame('published', $profile->fresh()->status);
        $this->assertSame('verified', $profile->fresh()->verification_status);
        $controller->unpublish($profile->fresh());
        $this->assertSame('approved', $profile->fresh()->status);
        $this->assertNull($profile->fresh()->published_at);
    }
}
