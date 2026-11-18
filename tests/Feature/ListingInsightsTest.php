<?php
namespace Tests\Feature;

use App\Models\ListingEvent;
use App\Services\ListingInsightsService;
use App\Services\ProviderPaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ListingInsightsTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        $this->withoutMiddleware([\Modules\Installer\app\Http\Middleware\SetupMiddleware::class, \Modules\Installer\app\Http\Middleware\PurchaseVerifyMiddleware::class]);
        config(['app.app_mode' => 'LIVE']);
    }

    private function client(string $type) {
        $model = ProviderPaystackService::model($type);
        $client = new $model;
        $values = ['name' => 'Listing', 'slug' => 'listing-'.$type, 'phone' => '0700000000', 'password' => bcrypt('password'),
            in_array($type, ['doctor','clinic']) ? 'email' : 'official_email' => $type.'@example.test'];
        if ($type === 'doctor') $values += ['department_id' => \Modules\Doctor\app\Models\Department::create(['slug' => 'specialty'])->id, 'location_id' => \Modules\Doctor\app\Models\Location::create(['status' => 1])->id];
        if ($type === 'doctor') $values += ['years_of_experience' => '1', 'image' => 'photo.png'];
        else $values += ['logo' => 'logo.png'];
        if ($type === 'clinic') $values += ['specialty' => 'Facility', 'licensing_body' => 'Body', 'description' => 'We offer friendly, professional care for patients and families, with clear advice and fair, transparent prices.', 'address' => 'Nairobi', 'educations' => 'Education', 'experience' => 'Experience', 'qualifications' => 'Qualifications'];
        if ($type === 'school') $values += ['institution_type' => 'College', 'ownership' => 'Private', 'about' => 'We offer friendly, professional care for patients and families, with clear advice and fair, transparent prices.', 'location' => 'Nairobi'];
        if ($type === 'supplier') $values += ['description' => 'We offer friendly, professional care for patients and families, with clear advice and fair, transparent prices.', 'address' => 'Nairobi'];
        $client->forceFill($values)->save();
        if ($type === 'doctor') $client->translations()->create(['lang_code' => 'en', 'licensing_body' => 'Body', 'about' => 'We offer friendly, professional care for patients and families, with clear advice and fair, transparent prices.', 'address' => 'Nairobi', 'educations' => 'Education', 'experience' => 'Experience', 'qualifications' => 'Qualifications']);
        \Modules\Doctor\app\Models\ProviderDocument::create(['provider_type' => $type, 'provider_id' => $client->id, 'licensing_body' => 'Body', 'registration_number' => 'REG123', 'file_path' => 'proof.pdf', 'status' => 'pending']);
        return $client;
    }

    public function test_each_dashboard_has_scoped_metrics_reminders_and_csv_exports(): void {
        foreach (['doctor','clinic','school','supplier'] as $type) {
            $client = $this->client($type);
            $this->actingAs($client, $type);
            ListingEvent::create(['provider_type' => $type, 'provider_id' => $client->id, 'event' => 'phone_click', 'day' => now()->toDateString(), 'visitor' => 'visitor']);
            ListingEvent::create(['provider_type' => $type, 'provider_id' => $client->id + 100, 'event' => 'phone_click', 'day' => now()->toDateString(), 'visitor' => 'other']);
            ListingEvent::create(['provider_type' => $type, 'provider_id' => $client->id, 'event' => 'phone_click', 'day' => now()->subDays(100)->toDateString(), 'visitor' => 'old']);
            $summary = app(ListingInsightsService::class)->summary($type, $client, 7);
            $this->assertSame(1, $summary['totals']['phone_click']);
            $this->assertCount(7, $summary['labels']);
            $this->assertNotEmpty($summary['reminders']);
            $html = view('provider.partials.listing-insights', ['insightType' => $type])->render();
            $this->assertStringContainsString('listing-engagement-chart', $html);
            $this->get(route('listing.report', ['type' => $type, 'days' => 7]))->assertOk()->assertDownload($type.'-listing-engagement.csv');
            auth($type)->logout();
        }
    }

    public function test_click_tracking_requires_signature_deduplicates_and_excludes_owner(): void {
        $client = $this->client('supplier');
        $url = URL::temporarySignedRoute('listing.track', now()->addHour(), ['type' => 'supplier', 'provider' => $client->id, 'event' => 'phone_click']);
        $this->getJson(route('listing.track', ['type' => 'supplier', 'provider' => $client->id, 'event' => 'phone_click']))->assertForbidden();
        $this->get($url)->assertNoContent();
        $this->withCookie(config('session.cookie'), app('session')->getId());
        $this->get($url)->assertNoContent();
        $this->assertSame(1, ListingEvent::count());
        $this->actingAs($client, 'supplier');
        $own = URL::temporarySignedRoute('listing.track', now()->addHour(), ['type' => 'supplier', 'provider' => $client->id, 'event' => 'website_click']);
        $this->get($own)->assertNoContent();
        $this->assertSame(1, ListingEvent::count());
    }

    public function test_reports_require_owner_login_and_valid_period(): void {
        $this->getJson(route('listing.report', ['type' => 'supplier']))->assertUnauthorized();
        $this->actingAs($this->client('supplier'), 'supplier');
        $this->getJson(route('listing.report', ['type' => 'supplier', 'days' => 999]))->assertUnprocessable();
        $this->getJson(route('listing.report', ['type' => 'school']))->assertUnauthorized();
    }

    public function test_incomplete_profiles_cannot_bypass_onboarding_through_new_features(): void {
        $client = $this->client('supplier');
        $client->forceFill(['logo' => null])->save();
        $this->actingAs($client, 'supplier');
        $this->getJson(route('listing.report', ['type' => 'supplier']))->assertForbidden();
        $this->getJson(route('listing.inbox', ['type' => 'supplier']))->assertForbidden();
    }

    public function test_public_profiles_and_directory_results_are_tracked_without_changing_layout(): void {
        $client = $this->client('supplier');
        request()->setLaravelSession(app('session')->driver());
        $middleware = app(\App\Http\Middleware\TrackListingEngagement::class);
        foreach (['patient.medical-suppliers' => ['suppliers' => collect([$client])], 'patient.medical-supplier-show' => ['supplier' => $client]] as $name => $data) {
            $view = \Mockery::mock(\Illuminate\Contracts\View\View::class);
            $view->shouldReceive('name')->andReturn($name);
            $view->shouldReceive('getData')->andReturn($data);
            $response = response('<body><main>Original layout</main></body>');
            $response->original = $view;
            $result = $middleware->handle(request(), fn () => $response);
            $this->assertStringContainsString('<main>Original layout</main>', $result->getContent());
            if ($name === 'patient.medical-supplier-show') $this->assertStringContainsString('listing-engagement', $result->getContent());
        }
        $this->assertDatabaseHas('listing_events', ['provider_type' => 'supplier', 'provider_id' => $client->id, 'event' => 'profile_view']);
        $this->assertDatabaseHas('listing_events', ['provider_type' => 'supplier', 'provider_id' => $client->id, 'event' => 'search_appearance']);
    }

    public function test_inquiry_status_updates_are_scoped_to_owner_for_every_type(): void {
        \Illuminate\Support\Facades\Cache::put('setting', (object) ['app_name' => 'Medfind Test', 'favicon' => '', 'logo' => '', 'default_avatar' => '', 'recaptcha_status' => 'inactive', 'version' => '1.0', 'doctor_can_add_social_links' => 'inactive']);
        foreach (['doctor','clinic','school','supplier'] as $type) {
            $client = $this->client($type);
            $profile = \App\Models\ProviderProfile::firstOrCreate(['provider_type' => $type, 'provider_id' => $client->id], ['status' => 'published']);
            if (in_array($type, ['school','supplier'])) {
                $client->forceFill(['provider_profile_id' => $profile->id])->save();
                $profile->subscriptions()->create(['status' => 'approved', 'package_name' => 'Test', 'plan_code' => 'premium', 'amount' => 1, 'currency' => 'KES', 'duration_months' => 1, 'starts_at' => now(), 'expires_at' => now()->addMonth(), 'metadata' => ['entitlements' => ['inquiries' => true]]]);
            } else {
                $client->subscriptions()->create(['package_name' => 'Test', 'plan_code' => 'premium', 'package_price' => 1, 'plan_duration' => '1 Month', 'status' => 'approved', 'approved_at' => now(), 'expires_at' => now()->addMonth(), 'entitlements' => ['inquiries' => true]]);
            }
            $this->actingAs($client, $type);
            $query = app(ListingInsightsService::class)->inquiries($type, $client);
            $values = in_array($type, ['doctor','clinic']) ? ['provider_type' => $type, 'provider_id' => $client->id, 'patient_name' => 'Visitor', 'phone' => '0700000001', 'reason' => 'Information', 'preferred_contact_method' => 'phone'] : ['name' => 'Visitor', 'email' => 'visitor@example.test', 'phone' => '0700000001', 'message' => 'Information'];
            $item = $query->create($values);
            $this->patchJson(route('listing.inbox.update', ['type' => $type, 'inquiry' => $item->id]), ['status' => 'contacted'])->assertRedirect();
            $this->assertSame('contacted', $item->fresh()->status);
            $this->patchJson(route('listing.inbox.update', ['type' => $type, 'inquiry' => $item->id]), ['status' => 'closed'])->assertRedirect();
            $this->assertSame('closed', $item->fresh()->status);
            $this->patchJson(route('listing.inbox.update', ['type' => $type, 'inquiry' => $item->id + 999]), ['status' => 'closed'])->assertNotFound();
            $this->get(route('listing.inbox', ['type' => $type, 'status' => 'closed']))->assertOk()->assertSee('Visitor');
            auth($type)->logout();
        }
    }
}
