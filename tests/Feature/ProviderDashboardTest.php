<?php

namespace Tests\Feature;

use App\Models\ProviderProfile;
use App\Models\ProviderSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Doctor\app\Services\SubscriptionPlanService;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;
use Tests\TestCase;

/** One dashboard design for all provider types; what it shows follows the package bought. */
class ProviderDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::put('setting', new class extends \stdClass {
            public $app_name = 'Medfind Test'; public $recaptcha_status = 'inactive'; public $version = '1.0'; public $cookie_status = 'inactive';
            public $logo = 'logo.png'; public $favicon = 'favicon.png'; public $default_avatar = 'avatar.png';
            public function __get($key) { return ''; }
            public function __isset($key) { return false; }
        });
        \Modules\Installer\app\Models\Configuration::create(['config' => 'setup_complete', 'value' => 1]);
        $this->withoutMiddleware([\App\Http\Middleware\DemoModeMiddleware::class, \App\Http\Middleware\SetLocaleMiddleware::class, \App\Http\Middleware\MaintenanceModeMiddleware::class,
            \App\Http\Middleware\TrackSiteVisit::class, \App\Http\Middleware\RequireProviderOnboarding::class]);
    }

    private function supplierOn(string $which): MedicalSupplier
    {
        $plans = collect(app(SubscriptionPlanService::class)->for('supplier'))->sortBy('price')->values();
        $plan = $which === 'top' ? $plans->last() : $plans->first();
        $supplier = MedicalSupplier::create(['name' => "Supplier $which", 'slug' => "supplier-$which", 'official_email' => "$which@example.test", 'password' => bcrypt('x'), 'email_verified_at' => now(), 'description' => 'd', 'address' => 'Nairobi']);
        $profile = ProviderProfile::create(['provider_type' => 'supplier', 'provider_id' => $supplier->id, 'status' => 'published', 'published_at' => now()]);
        $supplier->update(['provider_profile_id' => $profile->id]);
        ProviderSubscription::create(['provider_profile_id' => $profile->id, 'plan_code' => $plan['id'], 'package_name' => $plan['name'], 'amount' => $plan['price'], 'currency' => 'KES',
            'duration_months' => $plan['duration_months'], 'status' => 'approved', 'starts_at' => now()->subDay(), 'expires_at' => now()->addMonth(), 'metadata' => ['entitlements' => $plan['entitlements']]]);
        return $supplier;
    }

    public function test_dashboard_follows_the_package(): void
    {
        $basic = $this->supplierOn('basic');
        $this->actingAs($basic, 'supplier');
        $page = $this->get(route('website.partner.dashboard', ['type' => 'supplier']))->assertOk();
        $page->assertSee('Profile strength')->assertSee('Quick actions')->assertSee('Unlock with a higher plan');
        if (empty(app(SubscriptionPlanService::class)->for('supplier')[0]['entitlements']['analytics'])) $page->assertSee('Detailed analytics are not in your plan');
        auth('supplier')->logout();

        $top = $this->supplierOn('top');
        $this->actingAs($top, 'supplier');
        $this->get(route('website.partner.dashboard', ['type' => 'supplier']))->assertOk()
            ->assertDontSee('Detailed analytics are not in your plan')->assertSee('Download CSV report', false);
    }
}
