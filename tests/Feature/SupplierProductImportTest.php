<?php

namespace Tests\Feature;

use App\Models\ProviderProfile;
use App\Models\ProviderSubscription;
use App\Services\SupplierProductImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;
use Tests\TestCase;

class SupplierProductImportTest extends TestCase
{
    use RefreshDatabase;

    private function supplier(): MedicalSupplier
    {
        $record = MedicalSupplier::create(['name' => 'Supplier', 'slug' => 'supplier', 'official_email' => 'sup@example.test', 'password' => bcrypt('x'), 'email_verified_at' => now()]);
        $profile = ProviderProfile::create(['provider_type' => 'supplier', 'provider_id' => $record->id, 'status' => 'published', 'published_at' => now()]);
        $record->update(['provider_profile_id' => $profile->id]);
        ProviderSubscription::create(['provider_profile_id' => $profile->id, 'plan_code' => 'premium', 'package_name' => 'Premium', 'amount' => 5000, 'currency' => 'KES', 'duration_months' => 3, 'status' => 'approved', 'starts_at' => now()->subDay(), 'expires_at' => now()->addMonths(3)]);
        return $record;
    }

    private function csv(array $rows): UploadedFile
    {
        $lines = array_map(fn ($r) => implode(',', array_map(fn ($c) => '"'.str_replace('"', '""', $c).'"', $r)), $rows);
        return UploadedFile::fake()->createWithContent('products.csv', "\xEF\xBB\xBF".implode("\n", $lines)."\n");
    }

    public function test_rows_are_created_updated_and_errors_reported(): void
    {
        $supplier = $this->supplier();
        $supplier->products()->create(['name' => 'Wheelchair', 'slug' => 'wheelchair', 'approval_status' => 'approved', 'is_published' => true]);

        $result = app(SupplierProductImport::class)->import($supplier, $this->csv([
            ['Name', 'Brand', 'Model', 'Category', 'Availability', 'Description', 'Specifications'],
            ['Glucometer', 'Accu', 'G1', '', 'In stock', 'Blood sugar meter', ''],
            ['', 'x', '', '', '', '', ''],
            ['wheelchair', '', 'W2', '', 'Order', 'Folding', ''],
            ['Glucometer', '', '', '', '', '', ''],
            ['', '', '', '', '', '', ''],
        ]), 0, ['categories' => true, 'brands' => true]);

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['updated']);
        $this->assertCount(2, $result['errors']);
        $this->assertStringContainsString('Row 3', $result['errors'][0]);
        $this->assertStringContainsString('Row 5', $result['errors'][1]);
        $wheelchair = $supplier->products()->where('slug', 'wheelchair')->sole();
        $this->assertSame('W2', $wheelchair->model);
        $this->assertSame('pending', $wheelchair->approval_status, 'An edited product needs approval again.');
        $this->assertFalse($wheelchair->is_published);
        $this->assertSame('Accu', $supplier->products()->where('name', 'Glucometer')->sole()->brand);
    }

    public function test_product_limit_is_respected(): void
    {
        $supplier = $this->supplier();
        $result = app(SupplierProductImport::class)->import($supplier, $this->csv([['name'], ['A'], ['B'], ['C']]), 2, []);
        $this->assertSame(2, $result['created']);
        $this->assertSame(2, $supplier->products()->count());
        $this->assertStringContainsString('allows 2 products', $result['errors'][0]);
    }

    public function test_portal_upload_and_template(): void
    {
        $supplier = $this->supplier();
        \Modules\Installer\app\Models\Configuration::create(['config' => 'setup_complete', 'value' => 1]);
        \Illuminate\Support\Facades\Cache::put('setting', (object) ['app_name' => 'Medfind Test', 'favicon' => '', 'logo' => '', 'default_avatar' => '', 'recaptcha_status' => 'inactive', 'version' => '1.0']);
        $this->withoutMiddleware([\App\Http\Middleware\DemoModeMiddleware::class, \App\Http\Middleware\RequireProviderOnboarding::class, \App\Http\Middleware\SetLocaleMiddleware::class, \App\Http\Middleware\MaintenanceModeMiddleware::class, \App\Http\Middleware\TrackSiteVisit::class]);
        $this->actingAs($supplier, 'supplier');

        $this->get(route('website.partner.products.import-template', ['type' => 'supplier']))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->post(route('website.partner.products.import', ['type' => 'supplier']), ['file' => $this->csv([['name', 'model'], ['Oxygen concentrator', 'OC5']])])
            ->assertRedirect()->assertSessionHas('message');
        $this->assertSame('OC5', $supplier->products()->sole()->model);
        $this->get(route('website.partner.dashboard', ['type' => 'supplier']))->assertOk()->assertSee('Import products from a CSV file');
    }
}
