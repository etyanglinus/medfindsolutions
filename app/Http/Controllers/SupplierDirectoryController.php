<?php

namespace App\Http\Controllers;

use App\Models\ProviderProfile;
use App\Services\PartnerPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;
use Modules\MedicalSupplier\app\Models\QuotationRequest;
use Modules\MedicalSupplier\app\Models\SupplierAnalyticsEvent;
use Modules\MedicalSupplier\app\Models\SupplierCategory;
use Modules\MedicalSupplier\app\Models\SupplierProduct;

class SupplierDirectoryController extends Controller
{
    public function index(Request $request): View
    {
        $term = trim((string) $request->query('q', ''));
        $category = $request->query('category');
        $county = $request->query('county');
        $brand = trim((string) $request->query('brand', ''));
        $supplierType = trim((string) $request->query('supplier_type', ''));
        $profileIds = $this->visibleProfileIds();
        $brandProfileIds = ($brand !== '' || $term !== '') ? $this->visibleProfileIds('brands') : collect();
        $countyProfileIds = $county ? $this->visibleProfileIds('areas_served') : collect();
        $typeProfileIds = $supplierType !== '' ? $this->visibleProfileIds('supplier_types') : collect();
        $suppliers = MedicalSupplier::query()->whereIn('provider_profile_id', $profileIds)
            ->when($term !== '', fn ($q) => $q->where(fn ($s) => $s->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere(fn ($b) => $b->whereIn('provider_profile_id', $brandProfileIds)->whereJsonContains('brands', $term))
                ->orWhereHas('products', fn ($p) => $p->where('approval_status', 'approved')->where('is_published', true)->where(fn ($p) => $p->where('name', 'like', "%{$term}%")->orWhere(fn ($b) => $b->where('brand', 'like', "%{$term}%")->whereHas('supplier', fn ($s) => $s->whereIn('provider_profile_id', $brandProfileIds)))))))
            ->when($category, fn ($q) => $q->whereHas('categories', fn ($c) => $c->where('slug', $category)))
            ->when($county, fn ($q) => $q->whereIn('provider_profile_id', $countyProfileIds)->whereJsonContains('counties_served', $county))
            ->when($brand !== '', fn ($q) => $q->whereIn('provider_profile_id', $brandProfileIds)->where(fn ($w) => $w->whereJsonContains('brands', $brand)->orWhereHas('products', fn ($p) => $p->where('brand', $brand)->where('approval_status', 'approved')->where('is_published', true))))
            ->when($supplierType !== '', fn ($q) => $q->whereIn('provider_profile_id', $typeProfileIds)->whereJsonContains('supplier_types', $supplierType))
            ->with(['providerProfile.subscriptions', 'categories', 'products' => fn ($q) => $q->where('approval_status', 'approved')->where('is_published', true)->with('category')->latest()->take(4)])
            ->latest()->paginate(12)->withQueryString();
        $products = SupplierProduct::query()->where('approval_status', 'approved')->where('is_published', true)
            ->whereHas('supplier', fn ($q) => $q->whereIn('provider_profile_id', $profileIds))
            ->when($term !== '', fn ($q) => $q->where(fn ($p) => $p->where('name', 'like', "%{$term}%")->orWhere('brand', 'like', "%{$term}%")
                ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$term}%"))))
            ->when($category, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $category)))
            ->when($term !== '', fn ($q) => $q->where(fn ($p) => $p->where('name', 'like', "%{$term}%")->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$term}%"))->orWhere(fn ($b) => $b->where('brand', 'like', "%{$term}%")->whereHas('supplier', fn ($s) => $s->whereIn('provider_profile_id', $brandProfileIds)))))
            ->when($brand !== '', fn ($q) => $q->whereHas('supplier', fn ($s) => $s->whereIn('provider_profile_id', $brandProfileIds))->where('brand', $brand))
            ->when($county, fn ($q) => $q->whereHas('supplier', fn ($s) => $s->whereIn('provider_profile_id', $countyProfileIds)->whereJsonContains('counties_served', $county)))
            ->when($supplierType !== '', fn ($q) => $q->whereHas('supplier', fn ($s) => $s->whereIn('provider_profile_id', $typeProfileIds)->whereJsonContains('supplier_types', $supplierType)))
            ->with(['supplier.providerProfile.subscriptions', 'category'])->latest()->paginate(12, ['*'], 'products_page')->withQueryString();
        $portal = app(PartnerPortalService::class);
        $suppliers->getCollection()->transform(function ($supplier) use ($portal) {
            $supplier->public_entitlements = $supplier->providerProfile ? $portal->entitlements($supplier->providerProfile) : [];
            if (empty($supplier->public_entitlements['categories'])) $supplier->setRelation('categories', collect());
            if (empty($supplier->public_entitlements['supplier_types'])) $supplier->supplier_types = [];
            $supplier->public_products = $supplier->products->filter(fn ($product) => ! $product->is_featured || ! empty($supplier->public_entitlements['featured_products']));
            if (empty($supplier->public_entitlements['brands'])) { $supplier->brands = []; $supplier->public_products->each(fn ($product) => $product->brand = null); }
            if (empty($supplier->public_entitlements['services'])) $supplier->services = [];
            if (empty($supplier->public_entitlements['areas_served'])) $supplier->counties_served = [];
            if (empty($supplier->public_entitlements['gallery'])) $supplier->gallery_images = [];
            if (empty($supplier->public_entitlements['social_links'])) $supplier->social_links = [];
            if (empty($supplier->public_entitlements['promotional_banner']) || ! $supplier->promotional_banner_approved || ($supplier->promotional_banner_ends_at && $supplier->promotional_banner_ends_at->isPast())) $supplier->promotional_banner = null;
            return $supplier;
        });
        $suppliers->setCollection($suppliers->getCollection()->sortByDesc(fn ($supplier) => $supplier->is_featured && ! empty($supplier->public_entitlements['featured_supplier']))->values());
        $products->getCollection()->transform(function ($product) use ($portal) {
            $product->public_entitlements = $product->supplier?->providerProfile ? $portal->entitlements($product->supplier->providerProfile) : [];
            if (empty($product->public_entitlements['product_images'])) { $product->image = null; $product->additional_images = []; }
            else $product->additional_images = array_slice($product->additional_images ?? [], 0, max(0, (int) ($product->public_entitlements['additional_images_limit'] ?? 0)));
            if (empty($product->public_entitlements['brands'])) $product->brand = null;
            return $product;
        });
        $products->setCollection($products->getCollection()->sortByDesc(fn ($product) => $product->is_featured && ! empty($product->public_entitlements['featured_products']))->values());
        $categories = SupplierCategory::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $plans = app(\Modules\Doctor\app\Services\SubscriptionPlanService::class)->for('supplier');
        $allProfileIds = $this->visibleProfileIds();
        $supplierData = MedicalSupplier::with('providerProfile.subscriptions')->whereIn('provider_profile_id', $allProfileIds)->get();
        $brands = $supplierData->filter(fn ($s) => $portal->allows($s->providerProfile, 'brands'))->flatMap(fn ($s) => $s->brands ?? [])->filter()->unique()->sort()->values();
        $supplierTypes = $supplierData->filter(fn ($s) => $portal->allows($s->providerProfile, 'supplier_types'))->flatMap(fn ($s) => $s->supplier_types ?? [])->filter()->unique()->sort()->values();
        $counties = $supplierData->filter(fn ($s) => $portal->allows($s->providerProfile, 'areas_served'))->flatMap(fn ($s) => $s->counties_served ?? [])->filter()->unique()->sort()->values();
        return view('patient.medical-suppliers', compact('term', 'category', 'county', 'brand', 'supplierType', 'suppliers', 'products', 'categories', 'plans', 'brands', 'supplierTypes', 'counties'));
    }

    public function supplier(string $slug): View
    {
        $supplier = MedicalSupplier::query()->where('slug', $slug)->whereIn('provider_profile_id', $this->visibleProfileIds())
            ->with(['providerProfile.subscriptions', 'categories', 'products' => fn ($q) => $q->where('approval_status', 'approved')->where('is_published', true)->with('category')->latest()])
            ->firstOrFail();
        $supplier->public_entitlements = app(PartnerPortalService::class)->entitlements($supplier->providerProfile);
        $supplier->public_products = $supplier->products->filter(fn ($product) => ! $product->is_featured || ! empty($supplier->public_entitlements['featured_products']));
        if (empty($supplier->public_entitlements['brands'])) { $supplier->brands = []; $supplier->public_products->each(fn ($p) => $p->brand = null); }
        if (empty($supplier->public_entitlements['categories'])) $supplier->setRelation('categories', collect());
        if (empty($supplier->public_entitlements['services'])) $supplier->services = [];
        if (empty($supplier->public_entitlements['areas_served'])) $supplier->counties_served = [];
        if (empty($supplier->public_entitlements['social_links'])) $supplier->social_links = [];
        if (empty($supplier->public_entitlements['gallery'])) $supplier->gallery_images = [];
        if (empty($supplier->public_entitlements['promotional_banner']) || ! $supplier->promotional_banner_approved || ($supplier->promotional_banner_ends_at && $supplier->promotional_banner_ends_at->isPast())) $supplier->promotional_banner = null;
        $this->record($supplier, null, 'supplier_view');
        return view('patient.medical-supplier-show', compact('supplier'));
    }

    public function product(string $supplierSlug, string $productSlug): View
    {
        $supplier = MedicalSupplier::query()->where('slug', $supplierSlug)->whereIn('provider_profile_id', $this->visibleProfileIds())->firstOrFail();
        $product = $supplier->products()->where('slug', $productSlug)->where('approval_status', 'approved')->where('is_published', true)->with('category')->firstOrFail();
        $entitlements = app(PartnerPortalService::class)->entitlements($supplier->providerProfile);
        if (empty($entitlements['brands'])) $product->brand = null;
        if (empty($entitlements['product_images'])) { $product->image = null; $product->additional_images = []; }
        else $product->additional_images = array_slice($product->additional_images ?? [], 0, max(0, (int) ($entitlements['additional_images_limit'] ?? 0)));
        $this->record($supplier, $product, 'product_view');
        return view('patient.medical-supplier-product-show', compact('supplier', 'product', 'entitlements'));
    }

    public function quotation(Request $request, string $supplierSlug): RedirectResponse
    {
        $supplier = MedicalSupplier::query()->where('slug', $supplierSlug)->whereIn('provider_profile_id', $this->visibleProfileIds())->firstOrFail();
        abort_unless(app(PartnerPortalService::class)->allows($supplier->providerProfile, 'inquiries'), 404);
        $data = $request->validate([
            'medical_supplier_product_id' => ['nullable', 'integer', 'exists:medical_supplier_products,id'],
            'name' => ['required', 'string', 'max:255'], 'organisation' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'], 'email' => ['nullable', 'email', 'max:255'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:1000000'], 'message' => ['required', 'string', 'max:4000'],
        ]);
        if (empty($data['phone']) && empty($data['email'])) abort(422, __('Provide a phone number or email address.'));
        if (! empty($data['medical_supplier_product_id'])) abort_unless($supplier->products()->whereKey($data['medical_supplier_product_id'])->where('approval_status', 'approved')->where('is_published', true)->exists(), 422);
        $quote = DB::transaction(function () use ($supplier, $data) {
            $quote = $supplier->quotationRequests()->create($data + ['status' => 'new']);
            $quote->statusHistory()->create(['status' => 'new', 'actor_type' => 'requester']);
            return $quote;
        });
        $this->record($supplier, $quote->product, 'quotation');
        try {
            Mail::raw(__('A new quotation request was received from :name (:organisation). Contact: :phone :email. Product: :product. Quantity: :quantity. Message: :message', [
                'name' => $quote->name, 'organisation' => $quote->organisation ?: __('Not provided'), 'phone' => $quote->phone ?: __('Not provided'),
                'email' => $quote->email ?: __('Not provided'), 'product' => $quote->product?->name ?? __('General enquiry'),
                'quantity' => $quote->quantity ?? __('Not specified'), 'message' => $quote->message,
            ]), fn ($mail) => $mail->to($supplier->official_email)->replyTo($quote->email ?: $supplier->official_email, $quote->name)->subject(__('New MedFind quotation request')));
        } catch (\Throwable $exception) { report($exception); }
        return back()->with('message', __('Your quotation request was sent to the supplier.'));
    }

    public function catalogue(string $supplierSlug, int $productId)
    {
        $supplier = MedicalSupplier::query()->where('slug', $supplierSlug)->whereIn('provider_profile_id', $this->visibleProfileIds())->firstOrFail();
        abort_unless(app(PartnerPortalService::class)->allows($supplier->providerProfile, 'catalogue'), 404);
        $product = $supplier->products()->whereKey($productId)->where('approval_status', 'approved')->where('is_published', true)->where('catalogue_is_public', true)->firstOrFail();
        abort_unless($product->catalogue_path && Storage::disk('local')->exists($product->catalogue_path), 404);
        return Storage::disk('local')->download($product->catalogue_path, Str::slug($product->name).'-catalogue.pdf');
    }

    private function visibleProfileIds(?string $feature = null)
    {
        $profiles = ProviderProfile::query()->where('provider_type', ProviderProfile::TYPE_SUPPLIER)
            ->where('status', ProviderProfile::STATUS_PUBLISHED)->whereNotNull('published_at')
            ->whereHas('subscriptions', fn ($q) => $q->where('status', 'approved')->where(fn ($s) => $s->whereNull('starts_at')->orWhere('starts_at', '<=', now()))->where(fn ($s) => $s->whereNull('expires_at')->orWhere('expires_at', '>', now())))
            ->with('subscriptions')->get();
        $portal = app(PartnerPortalService::class);
        return $profiles->filter(fn ($profile) => $portal->allows($profile, 'search_visibility') && $portal->allows($profile, 'supplier_profile') && (! $feature || $portal->allows($profile, $feature)))->pluck('id');
    }

    private function record(MedicalSupplier $supplier, ?SupplierProduct $product, string $type): void
    {
        $profile = $supplier->providerProfile;
        if (! $profile || ! app(PartnerPortalService::class)->allows($profile, 'analytics')) return;
        $hash = hash_hmac('sha256', request()->ip().'|'.request()->userAgent(), (string) config('app.key'));
        if ($type !== 'quotation' && ! Cache::add('supplier-analytics:'.$type.':'.$supplier->id.':'.($product?->id ?? 0).':'.$hash, true, now()->addMinutes(15))) return;
        SupplierAnalyticsEvent::create(['medical_supplier_id' => $supplier->id, 'medical_supplier_product_id' => $product?->id, 'event_type' => $type, 'visitor_hash' => $hash]);
    }
}
