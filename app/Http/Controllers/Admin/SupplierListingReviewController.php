<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Services\PartnerPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;
use Modules\MedicalSupplier\app\Models\SupplierProduct;

class SupplierListingReviewController extends Controller
{
    public function index(Request $request): View
    {
        checkAdminHasPermissionAndThrowException('supplier.view');
        $suppliers = ProviderProfile::query()->where('provider_type', ProviderProfile::TYPE_SUPPLIER)
            ->with(['provider', 'subscriptions' => fn ($q) => $q->latest(), 'provider.documents', 'provider.products' => fn ($q) => $q->latest()])
            ->latest()->paginate(20, ['*'], 'suppliers_page')->withQueryString();
        $products = SupplierProduct::query()->whereHas('supplier')->with('supplier.providerProfile')
            ->when($request->filled('product_status'), fn ($q) => $q->where('approval_status', $request->input('product_status')))
            ->when(! $request->filled('product_status'), fn ($q) => $q->whereIn('approval_status', ['pending', 'changes_required']))
            ->latest()->paginate(30, ['*'], 'products_page')->withQueryString();
        return view('admin.supplier_listings.index', compact('suppliers', 'products'));
    }

    public function updateProduct(Request $request, SupplierProduct $product): RedirectResponse
    {
        checkAdminHasPermissionAndThrowException('supplier.update');
        $data = $request->validate(['approval_status' => ['required', Rule::in(['approved', 'rejected', 'changes_required'])], 'admin_note' => ['nullable', 'string', 'max:1000']]);
        $supplier = $product->supplier()->with('providerProfile')->firstOrFail();
        $entitlements = app(PartnerPortalService::class)->entitlements($supplier->providerProfile);
        $product->update([
            'approval_status' => $data['approval_status'], 'admin_note' => $data['admin_note'] ?? null,
            'is_published' => $data['approval_status'] === 'approved',
            'is_featured' => $data['approval_status'] === 'approved' && $product->is_featured && ! empty($entitlements['featured_products']),
        ]);
        return back()->with(['message' => __('Product review saved.'), 'alert-type' => 'success']);
    }

    public function featureSupplier(Request $request, MedicalSupplier $supplier): RedirectResponse
    {
        checkAdminHasPermissionAndThrowException('supplier.update');
        $data = $request->validate(['featured' => ['required', 'boolean']]);
        $supplier->update(['is_featured' => (bool) $data['featured']]);
        return back()->with(['message' => __('Supplier placement updated.'), 'alert-type' => 'success']);
    }

    public function reviewBanner(Request $request, MedicalSupplier $supplier): RedirectResponse
    {
        checkAdminHasPermissionAndThrowException('supplier.update');
        $data = $request->validate(['approved' => ['required', 'boolean']]);
        $supplier->update(['promotional_banner_approved' => (bool) $data['approved']]);
        return back()->with(['message' => __('Promotional banner review saved.'), 'alert-type' => 'success']);
    }
}
