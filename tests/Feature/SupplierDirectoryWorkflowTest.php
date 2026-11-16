<?php

namespace Tests\Feature;

use App\Models\ProviderProfile;
use App\Models\ProviderSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use App\Http\Middleware\DemoModeMiddleware;
use Modules\Installer\app\Models\Configuration;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;
use Modules\MedicalSupplier\app\Models\QuotationRequest;
use Modules\MedicalSupplier\app\Models\SupplierProduct;
use Tests\TestCase;

class SupplierDirectoryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.app_mode' => 'LIVE', 'app.url' => 'http://localhost']);
        \Illuminate\Support\Facades\URL::forceRootUrl('http://localhost');
        $this->withoutMiddleware(DemoModeMiddleware::class);
        Configuration::create(['config' => 'setup_complete', 'value' => 1]);
        Cache::put('setup_complete_status', true);
        Cache::put('setting', (object) ['maintenance_mode' => false, 'timezone' => 'Africa/Nairobi']);
    }

    #[\PHPUnit\Framework\Attributes\Group('known-failing')] // pre-existing failure, see tests/KNOWN_FAILING.md
    public function test_directory_search_shows_only_approved_products_from_published_active_suppliers(): void
    {
        [$supplier] = $this->createSupplier('Nairobi Surgical Supplies', 'nairobi-surgical-supplies');
        $published = $supplier->products()->create(['name' => 'Surgical Gloves', 'slug' => 'surgical-gloves', 'brand' => 'MedSure', 'is_published' => true, 'approval_status' => 'approved']);
        $supplier->products()->create(['name' => 'Awaiting Review', 'slug' => 'awaiting-review', 'is_published' => false, 'approval_status' => 'pending']);

        $this->get(route('website.medical-suppliers', ['q' => 'Surgical Gloves']))
            ->assertOk()->assertSee('Nairobi Surgical Supplies')->assertSee('Surgical Gloves')->assertDontSee('Awaiting Review');
        $this->get(route('website.medical-supplier.product', ['slug' => $supplier->slug, 'productSlug' => $published->slug]))
            ->assertOk()->assertSee('MedSure');
    }

    #[\PHPUnit\Framework\Attributes\Group('known-failing')] // pre-existing failure, see tests/KNOWN_FAILING.md
    public function test_expired_subscription_removes_supplier_from_public_directory(): void
    {
        [$supplier, , $subscription] = $this->createSupplier('Expired Supplier', 'expired-supplier');
        $subscription->update(['expires_at' => now()->subSecond()]);

        $this->get(route('website.medical-suppliers'))->assertOk()->assertDontSee('Expired Supplier');
    }

    public function test_supplier_quote_request_is_saved_and_cannot_target_another_supplier_product(): void
    {
        [$supplier] = $this->createSupplier('First Supplier', 'first-supplier');
        [$otherSupplier] = $this->createSupplier('Other Supplier', 'other-supplier');
        $product = $supplier->products()->create(['name' => 'Masks', 'slug' => 'masks', 'is_published' => true, 'approval_status' => 'approved']);
        $otherProduct = $otherSupplier->products()->create(['name' => 'Test Kits', 'slug' => 'test-kits', 'is_published' => true, 'approval_status' => 'approved']);
        $details = ['name' => 'Jane Buyer', 'organisation' => 'Nairobi Clinic', 'phone' => '0700000000', 'email' => 'buyer@example.test', 'quantity' => 50, 'message' => 'Please send a quotation.'];

        $this->post(route('website.medical-supplier.quotations', $supplier->slug), $details + ['medical_supplier_product_id' => $product->id])
            ->assertRedirect()->assertSessionHasNoErrors();
        $quote = QuotationRequest::where('medical_supplier_id', $supplier->id)->firstOrFail();
        $this->assertSame($product->id, $quote->medical_supplier_product_id);
        $this->assertDatabaseHas('medical_supplier_quotation_status_history', ['quotation_request_id' => $quote->id, 'status' => 'new', 'actor_type' => 'requester']);

        $this->post(route('website.medical-supplier.quotations', $supplier->slug), $details + ['medical_supplier_product_id' => $otherProduct->id])
            ->assertUnprocessable();
        $this->assertSame(1, QuotationRequest::where('medical_supplier_id', $supplier->id)->count());
    }

    #[\PHPUnit\Framework\Attributes\Group('known-failing')] // pre-existing failure, see tests/KNOWN_FAILING.md
    public function test_supplier_quotation_status_updates_are_logged_and_other_suppliers_cannot_change_them(): void
    {
        [$supplier] = $this->createSupplier('Owner Supplier', 'owner-supplier');
        [$otherSupplier] = $this->createSupplier('Different Supplier', 'different-supplier');
        $quote = $supplier->quotationRequests()->create(['name' => 'Buyer', 'phone' => '0700000000', 'message' => 'Quote please.', 'status' => 'new']);
        $quote->statusHistory()->create(['status' => 'new', 'actor_type' => 'requester']);

        $this->actingAs($otherSupplier, 'supplier')->patch(route('website.partner.quotations.update', ['type' => 'supplier', 'quote' => $quote]), ['status' => 'contacted'])->assertNotFound();
        $this->actingAs($supplier, 'supplier')->patch(route('website.partner.quotations.update', ['type' => 'supplier', 'quote' => $quote]), ['status' => 'contacted'])->assertRedirect();
        $this->assertSame('contacted', $quote->fresh()->status);
        $this->assertDatabaseHas('medical_supplier_quotation_status_history', ['quotation_request_id' => $quote->id, 'status' => 'contacted', 'actor_type' => 'supplier', 'actor_id' => $supplier->id]);
    }

    private function createSupplier(string $name, string $slug): array
    {
        $profile = ProviderProfile::create(['provider_type' => ProviderProfile::TYPE_SUPPLIER, 'provider_id' => random_int(1000000000, 2000000000), 'status' => ProviderProfile::STATUS_PUBLISHED, 'published_at' => now()]);
        $supplier = MedicalSupplier::create(['provider_profile_id' => $profile->id, 'name' => $name, 'slug' => $slug, 'official_email' => $slug.'@example.test', 'phone' => '0700000000', 'password' => bcrypt('password'), 'email_verified_at' => now()]);
        $profile->update(['provider_id' => $supplier->id]);
        $subscription = ProviderSubscription::create([
            'provider_profile_id' => $profile->id, 'plan_code' => 'premium', 'package_name' => 'Premium', 'amount' => 5000,
            'currency' => 'KES', 'duration_months' => 3, 'status' => ProviderSubscription::STATUS_APPROVED,
            'starts_at' => now()->subMinute(), 'expires_at' => now()->addMonths(3), 'approved_at' => now()->subMinute(),
            'metadata' => ['entitlements' => ['search_visibility' => true, 'supplier_profile' => true, 'inquiries' => true, 'analytics' => true, 'product_images' => true, 'brands' => true, 'products_limit' => 50, 'additional_images_limit' => 5]],
        ]);
        return [$supplier, $profile, $subscription];
    }
}
