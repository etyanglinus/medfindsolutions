<?php

namespace Tests\Feature;

use App\Http\Controllers\SeoLandingController;
use App\Http\Controllers\SitemapController;
use App\Models\ProviderProfile;
use App\Models\ProviderSubscription;
use App\Services\PublicDirectory;
use App\Support\StructuredData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Doctor\app\Models\Clinic;
use Modules\Doctor\app\Models\Doctor;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class SeoLandingPagesTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\CompletesProviderProfiles;

    private int $cardiology;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cardiology = DB::table('departments')->insertGetId(['slug' => 'cardiologist', 'status' => 1]);
        DB::table('department_translations')->insert(['department_id' => $this->cardiology, 'lang_code' => 'en', 'name' => 'Cardiologist', 'description' => 'x']);
        PublicDirectory::forget();
    }

    private function doctor(string $slug, string $address, bool $listed = true): Doctor
    {
        $location = DB::table('locations')->insertGetId(['status' => 1]);
        $doctor = Doctor::create(['department_id' => $this->cardiology, 'location_id' => $location, 'name' => ucfirst($slug), 'slug' => $slug, 'email' => "$slug@example.test", 'phone' => '0700', 'password' => 'x', 'status' => 1, 'email_verified_at' => now()]);
        DB::table('doctor_translations')->insert(['doctor_id' => $doctor->id, 'lang_code' => 'en', 'address' => $address]);
        $this->completeDoctor($doctor);
        $doctor->subscriptions()->create(['package_name' => 'Basic', 'plan_code' => 'basic', 'package_price' => 1000, 'plan_duration' => 1, 'payment_method' => 'paystack', 'status' => 'approved', 'approved_at' => now()->subDay(),
            'expires_at' => $listed ? now()->addMonth() : now()->subDay(), 'transaction_code' => "T-$slug", 'entitlements' => ['search_visibility' => true]]);
        return $doctor;
    }

    public function test_specialty_and_area_pages_exist_only_where_specialists_are_listed(): void
    {
        $this->doctor('dr-west', 'Westlands, Nairobi');
        $this->doctor('dr-kisumu', 'Milimani, Kisumu');
        $this->doctor('dr-expired', 'Westlands, Nairobi', listed: false);
        $pages = app(SeoLandingController::class);

        $view = $pages->specialty('cardiologist');
        $this->assertSame('Cardiologists in Kenya', $view->getData()['h1']);
        $this->assertSame(2, $view->getData()['results']->total());
        $this->assertEqualsCanonicalizing(['Westlands', 'Nairobi', 'Kisumu'], collect($view->getData()['links']['items'])->pluck('label')->all());

        $west = $pages->specialtyPlace('cardiologist', 'westlands');
        $this->assertSame(['Dr-west'], $west->getData()['results']->pluck('name')->all(), 'Expired plans are not listed.');

        foreach ([fn () => $pages->specialtyPlace('cardiologist', 'nakuru'), fn () => $pages->specialty('dentist'), fn () => $pages->specialtyPlace('cardiologist', 'atlantis')] as $missing) {
            try { $missing(); $this->fail('Expected a 404 for a page with no providers.'); } catch (NotFoundHttpException) { $this->addToAssertionCount(1); }
        }
    }

    public function test_facility_and_supplier_county_pages(): void
    {
        $clinic = Clinic::create(['name' => 'Kilimani Clinic', 'slug' => 'kilimani-clinic', 'email' => 'k@example.test', 'phone' => '0700', 'password' => 'x', 'status' => 1, 'location' => 'Kilimani, Nairobi']);
        $this->completeClinic($clinic);
        $clinic->subscriptions()->create(['package_name' => 'Basic', 'plan_code' => 'basic', 'package_price' => 3000, 'plan_duration' => 1, 'payment_method' => 'paystack', 'status' => 'approved', 'approved_at' => now()->subDay(), 'expires_at' => now()->addMonth(), 'transaction_code' => 'T-k', 'entitlements' => ['search_visibility' => true]]);
        $supplier = MedicalSupplier::create(['name' => 'Kiambu Supplies', 'slug' => 'kiambu-supplies', 'official_email' => 's@example.test', 'password' => 'x', 'counties_served' => ['Kiambu', 'Nairobi']]);
        $profile = ProviderProfile::create(['provider_type' => 'supplier', 'provider_id' => $supplier->id, 'status' => 'published', 'published_at' => now()]);
        $supplier->update(['provider_profile_id' => $profile->id]);
        ProviderSubscription::create(['provider_profile_id' => $profile->id, 'plan_code' => 'standard', 'package_name' => 'Standard', 'amount' => 3000, 'currency' => 'KES', 'duration_months' => 1, 'status' => 'approved', 'starts_at' => now()->subDay(), 'expires_at' => now()->addMonth()]);
        $pages = app(SeoLandingController::class);

        $this->assertSame('Medical facilities in Kilimani', $pages->facilities('kilimani')->getData()['h1']);
        $this->assertSame('Medical suppliers in Kiambu County', $pages->suppliers('kiambu')->getData()['h1']);
        $this->expectException(NotFoundHttpException::class);
        $pages->suppliers('westlands'); // suppliers are listed by county only
    }

    public function test_sitemap_lists_landing_pages_and_profiles_but_not_unlisted_providers(): void
    {
        $this->doctor('dr-west', 'Westlands, Nairobi');
        $this->doctor('dr-expired', 'Upper Hill, Nairobi', listed: false);

        $xml = app(SitemapController::class)->sitemap(app(PublicDirectory::class))->getContent();
        $urls = simplexml_load_string($xml)->url;
        $locs = collect(iterator_to_array($urls, false))->map(fn ($u) => (string) $u->loc);

        $this->assertTrue($locs->contains(route('website.seo.specialty', 'cardiologist')));
        $this->assertTrue($locs->contains(route('website.seo.specialty-place', ['cardiologist', 'westlands'])));
        $this->assertTrue($locs->contains(route('website.doctor.details', 'dr-west')));
        $this->assertFalse($locs->contains(route('website.doctor.details', 'dr-expired')));
        $this->assertFalse($locs->contains(route('website.seo.specialty-place', ['cardiologist', 'upper-hill'])));
        $this->assertSame($locs->count(), $locs->unique()->count());
    }

    public function test_robots_points_to_the_sitemap_and_keeps_crawlers_out_of_portals(): void
    {
        $robots = app(SitemapController::class)->robots()->getContent();
        $this->assertStringContainsString('Sitemap: '.route('sitemap'), $robots);
        foreach (['/admin', '/doctor/', '/clinic/', '/partner/'] as $private) $this->assertStringContainsString("Disallow: $private", $robots);
    }

    public function test_structured_data_cannot_break_out_of_its_script_tag(): void
    {
        $doctor = $this->doctor('dr-evil', 'Westlands');
        $doctor->name = 'Dr </script><script>alert(1)</script>';
        $html = view('seo.jsonld', ['data' => StructuredData::physician($doctor)])->render();

        $this->assertSame(1, substr_count($html, '</script>'));
        $json = json_decode(trim(strip_tags($html)), true);
        $this->assertSame('Physician', $json['@type']);
        $this->assertSame('Cardiologist', $json['medicalSpecialty']);
    }
}
