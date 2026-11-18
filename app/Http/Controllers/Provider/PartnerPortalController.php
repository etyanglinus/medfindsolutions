<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Services\PartnerPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\HealthcareInstitution\app\Models\HealthcareCourse;
use Modules\HealthcareInstitution\app\Models\HealthcareInstitutionEnquiry;
use Modules\MedicalSupplier\app\Models\SupplierCategory;
use Modules\MedicalSupplier\app\Models\SupplierProduct;
use Modules\MedicalSupplier\app\Models\QuotationRequest;

class PartnerPortalController extends Controller
{
    public function __construct(private PartnerPortalService $portal) {}

    private function context(string $type): array
    {
        $definition = PartnerAuthController::definition($type);
        $record = auth($definition['guard'])->user();
        abort_unless($record, 403);
        $profile = ProviderProfile::where('provider_type', $definition['profile_type'])->where('provider_id', $record->id)->firstOrFail();
        return [$record, $profile, $this->portal->subscription($profile), $this->portal->entitlements($profile)];
    }

    private function requireFeature(ProviderProfile $profile, string $feature): void
    {
        abort_unless($this->portal->allows($profile, $feature), 403, __('This feature requires an active subscription package that includes it.'));
    }

    public function dashboard(string $type): View
    {
        [$record, $profile, $subscription, $entitlements] = $this->context($type);
        $items = $type === 'school' ? $record->courses()->latest()->get() : $record->products()->with('category')->latest()->get();
        $categories = $type === 'supplier' ? SupplierCategory::with('parent')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get() : collect();
        $quotes = $type === 'supplier' && ! empty($entitlements['inquiries']) ? $record->quotationRequests()->with('product')->latest()->take(8)->get() : collect();
        $enquiries = $type === 'school' && ! empty($entitlements['inquiries']) ? $record->enquiries()->with('course')->latest()->take(10)->get() : collect();
        $paymentHistory = $profile->paymentTransactions()->with('subscription')->latest()->take(10)->get();
        $analytics = $type === 'supplier' && ! empty($entitlements['analytics']) ? [
            'profile_views' => $record->analyticsEvents()->where('event_type', 'supplier_view')->where('created_at', '>=', now()->subDays(30))->count(),
            'product_views' => $record->analyticsEvents()->where('event_type', 'product_view')->where('created_at', '>=', now()->subDays(30))->count(),
            'quotation_requests' => $record->analyticsEvents()->where('event_type', 'quotation')->where('created_at', '>=', now()->subDays(30))->count(),
        ] : [];
        $dash = app(\App\Services\ProviderDashboard::class)->build($type, $record, (int) request('days', 30));
        return view('provider.partner-dashboard', compact('type', 'record', 'profile', 'subscription', 'entitlements', 'items', 'categories', 'quotes', 'enquiries', 'paymentHistory', 'analytics', 'dash'));
    }

    public function profile(string $type): View
    {
        [$record, $profile, $subscription, $entitlements] = $this->context($type);
        $categories = $type === 'supplier' ? SupplierCategory::with('parent')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get() : collect();
        return view('provider.partner-profile', compact('type', 'record', 'profile', 'subscription', 'entitlements', 'categories'));
    }

    public function updateProfile(Request $request, string $type): RedirectResponse
    {
        [$record, $profile, , $entitlements] = $this->context($type);
        abort_if(! $this->portal->subscription($profile), 403, __('An active package is required to edit the public profile.'));
        $schema = $type === 'school' ? 'healthcare_institutions' : 'medical_suppliers';
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'url', 'max:255'],
            'about' => [Rule::requiredIf($type === 'school'), 'nullable', 'string', 'max:5000'],
            'description' => [Rule::requiredIf($type === 'supplier'), 'nullable', 'string', 'max:5000'],
            'location' => [Rule::requiredIf($type === 'school'), 'nullable', 'string', 'max:255'],
            'institution_type' => [Rule::requiredIf(fn () => $type === 'school'), 'nullable', 'string', 'max:100'],
            'ownership' => [Rule::requiredIf(fn () => $type === 'school'), 'nullable', 'string', 'max:100'],
            'registration_number' => ['nullable', 'string', 'max:150'],
            'accreditation_body' => ['nullable', 'string', 'max:255'],
            'accreditation_expires_at' => ['nullable', 'date'],
            'application_url' => ['nullable', 'url', 'max:1000'],
            'location_map_url' => ['nullable', 'url', 'max:1000'],
            'intake_campaign_title' => ['nullable', 'string', 'max:255'],
            'intake_campaign_ends_at' => ['nullable', 'date', 'after_or_equal:today'],
            'facilities' => ['nullable', 'string', 'max:3000'],
            'practical_training' => ['nullable', 'string', 'max:3000'],
            'accommodation' => ['nullable', 'string', 'max:3000'],
            'international_pathways' => ['nullable', 'string', 'max:3000'],
            'address' => [Rule::requiredIf($type === 'supplier'), 'nullable', 'string', 'max:255'],
            'logo' => [$record->logo ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'year_established' => ['nullable', 'integer', 'between:1800,' . now()->year],
            'registration_number' => ['nullable', 'string', 'max:150'],
            'verification_body' => ['nullable', 'string', 'max:255'],
            'supplier_types' => ['sometimes', 'array', 'max:20'], 'supplier_types.*' => ['string', 'max:100', 'distinct'],
            'counties_served' => ['sometimes', 'array', 'max:47'], 'counties_served.*' => ['string', 'max:100', 'distinct'],
            'brands' => ['sometimes', 'array', 'max:100'], 'brands.*' => ['string', 'max:100', 'distinct'],
            'services' => ['sometimes', 'array', 'max:50'], 'services.*' => ['string', 'max:3000'],
            'social_links' => ['sometimes', 'array'], 'social_links.facebook' => ['nullable', 'url', 'max:500'], 'social_links.instagram' => ['nullable', 'url', 'max:500'], 'social_links.linkedin' => ['nullable', 'url', 'max:500'], 'social_links.x' => ['nullable', 'url', 'max:500'], 'social_links.youtube' => ['nullable', 'url', 'max:500'],
            'remove_gallery_images' => ['sometimes', 'array'], 'remove_gallery_images.*' => ['string', 'max:500'],
            'gallery_images' => ['sometimes', 'array', 'max:20'], 'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'promotional_banner_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'promotional_banner_ends_at' => ['nullable', 'date', 'after_or_equal:today'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'distinct', Rule::exists('medical_supplier_categories', 'id')->where('is_active', true)],
        ]);
        if ($type === 'supplier' && isset($data['about'])) { $data['description'] = $data['about']; unset($data['about']); }
        foreach ($data as $field => $value) {
            if (! in_array($field, ['name', 'contact_person', 'phone', 'logo', 'cover_image', 'category_ids', 'gallery_images', 'remove_gallery_images', 'promotional_banner_file'], true)
                && ! Schema::hasColumn($schema, $field)) unset($data[$field]);
        }
        $data = $this->storeImages($request, $data, ['logo' => 'partner-logos', 'cover_image' => 'partner-covers']);
        if ($type === 'school') {
            if (empty($entitlements['whatsapp'])) unset($data['whatsapp']);
            if (empty($entitlements['website'])) unset($data['website']);
            if (empty($entitlements['location_map'])) unset($data['location_map_url']);
            if (empty($entitlements['application_information'])) unset($data['application_url']);
            foreach (['facilities' => 'campus_facilities', 'practical_training' => 'practical_training', 'accommodation' => 'accommodation', 'international_pathways' => 'international_pathways'] as $field => $feature) {
                if (empty($entitlements[$feature])) unset($data[$field]);
            }
            if (empty($entitlements['intake_promotion'])) { unset($data['intake_campaign_title'], $data['intake_campaign_ends_at']); }
        } else {
            if (isset($data['about'])) { $data['description'] = $data['about']; unset($data['about']); }
            foreach (['whatsapp', 'website'] as $field) if (empty($entitlements[$field])) unset($data[$field]);
            foreach (['categories' => ['category_ids'], 'supplier_types' => ['supplier_types'], 'brands' => ['brands'], 'services' => ['services'], 'areas_served' => ['counties_served'], 'social_links' => ['social_links']] as $feature => $fields) {
                if (empty($entitlements[$feature])) foreach ($fields as $field) unset($data[$field]);
            }
            if (empty($entitlements['promotional_banner'])) unset($data['promotional_banner_ends_at'], $data['promotional_banner_file']);
            if (empty($entitlements['gallery'])) unset($data['gallery_images'], $data['remove_gallery_images']);
            else {
                $existing = $record->gallery_images ?? [];
                $remove = array_values(array_intersect($existing, $data['remove_gallery_images'] ?? []));
                foreach ($remove as $path) if (str_starts_with($path, 'supplier-gallery/'.$record->id.'/')) Storage::disk('public')->delete($path);
                $gallery = array_values(array_diff($existing, $remove));
                $uploads = $request->file('gallery_images', []);
                $galleryLimit = max(0, (int) ($entitlements['gallery_limit'] ?? 0));
                abort_if(count($gallery) + count($uploads) > $galleryLimit, 422, __('Your package allows up to :limit gallery images.', ['limit' => $galleryLimit]));
                foreach ($uploads as $upload) $gallery[] = $upload->storePublicly('supplier-gallery/'.$record->id, 'public');
                $data['gallery_images'] = $gallery;
                unset($data['remove_gallery_images']);
            }
            if (! empty($entitlements['promotional_banner']) && $request->hasFile('promotional_banner_file')) {
                $data['promotional_banner'] = $request->file('promotional_banner_file')->storePublicly('supplier-banners/'.$record->id, 'public');
                $data['promotional_banner_approved'] = false;
            }
            unset($data['promotional_banner_file']);
            if (isset($data['brands'])) {
                $brandList = array_values(array_unique(array_filter(array_map('trim', preg_split('/[,\r\n]+/', implode(',', $data['brands']))))));
                $data['brands'] = array_slice($brandList, 0, 100);
            }
            if (isset($data['services'])) {
                $serviceList = array_values(array_unique(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', implode("\n", $data['services']))))));
                abort_if(count($serviceList) > 50 || collect($serviceList)->contains(fn ($service) => mb_strlen($service) > 255), 422, __('Services must have a maximum of 50 entries, 255 characters each.'));
                $data['services'] = $serviceList;
            }
        }
        if (! Schema::hasColumn($schema, 'contact_person')) unset($data['contact_person']);
        if ($request->filled('name') && $request->input('name') !== $record->name) {
            $data['slug'] = $this->uniqueSlug($record, $request->input('name'));
            $data['name'] = $request->input('name');
        } else {
            $data['name'] = $record->name;
        }
        DB::transaction(function () use ($record, $data, $request, $type, $entitlements) {
            $record->fill($data)->save();
            if ($type === 'supplier' && ! empty($entitlements['categories'])) {
                $ids = SupplierCategory::where('is_active', true)->whereIn('id', $request->input('category_ids', []))->pluck('id')->all();
                $record->categories()->sync($ids);
            }
            unset($data['category_ids']);
        });
        return back()->with('message', __('Business profile updated.'));
    }

    public function account(string $type): View
    {
        [$record, $profile, $subscription, $entitlements] = $this->context($type);
        return view('provider.partner-account', compact('type', 'record', 'profile', 'subscription', 'entitlements'));
    }

    public function updateAccount(Request $request, string $type): RedirectResponse
    {
        [$record] = $this->context($type);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'current_password' => ['required_with:password', 'string'],
            'password' => ['nullable', 'confirmed', 'min:8', 'max:100'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
        if (! empty($data['password'])) {
            abort_unless(Hash::check($data['current_password'] ?? '', $record->password), 422, __('Current password is incorrect.'));
            $record->password = Hash::make($data['password']);
        }
        $record->name = $data['name'];
        if ($request->hasFile('avatar')) {
            $record->logo = $request->file('avatar')->storePublicly('partner-avatars', 'public');
        }
        $record->save();
        return back()->with('message', __('Account settings updated.'));
    }

    public function saveCourse(Request $request, string $type, ?HealthcareCourse $course = null): RedirectResponse
    {
        abort_unless($type === 'school', 404);
        [$record, $profile] = $this->context($type);
        $this->requireFeature($profile, 'institution_profile');
        abort_if($course && $course->healthcare_institution_id !== $record->id, 404);
        $limit = $this->portal->limit($profile, 'courses_limit');
        abort_if($limit === null, 403, __('An active package is required to manage courses.'));
        abort_if(! $course && $limit !== null && $limit > 0 && $record->courses()->count() >= $limit, 403, __('You have reached your course limit. Upgrade your package to add more courses.'));
        $entitlements = $this->portal->entitlements($profile);
        $rules = [
            'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:5000'],
            'intake_information' => ['nullable', 'string', 'max:3000'], 'fees' => ['nullable', 'string', 'max:255'],
            'application_information' => ['nullable', 'string', 'max:3000'],
            'intake_starts_at' => ['nullable', 'date'], 'intake_ends_at' => ['nullable', 'date', 'after_or_equal:intake_starts_at'],
        ];
        if (! empty($entitlements['featured_courses'])) $rules['is_featured'] = ['sometimes', 'boolean'];
        $data = $request->validate($rules);
        $data['is_featured'] = ! empty($entitlements['featured_courses']) && $request->boolean('is_featured');
        $slug = Str::slug($data['name']);
        $base = $slug; $index = 1;
        while ($record->courses()->where('slug', $slug)->when($course, fn ($q) => $q->where('id', '!=', $course->id))->exists()) $slug = $base . '-' . $index++;
        $data['slug'] = $slug;
        $data['approval_status'] = 'pending';
        $data['admin_note'] = null; // a resubmission clears the previous review note
        if (empty($entitlements['featured_courses'])) $data['is_featured'] = false;
        if ($course) $course->update($data); else $record->courses()->create($data);
        return back()->with('message', __('Course saved and submitted for admin review.'));
    }

    public function deleteCourse(string $type, HealthcareCourse $course): RedirectResponse
    {
        abort_unless($type === 'school', 404);
        [$record, $profile] = $this->context($type); $this->requireFeature($profile, 'institution_profile');
        abort_unless($course->healthcare_institution_id === $record->id, 404);
        $course->delete(); return back()->with('message', __('Course removed.'));
    }

    public function saveProduct(Request $request, string $type, ?SupplierProduct $product = null): RedirectResponse
    {
        abort_unless($type === 'supplier', 404);
        [$record, $profile, , $entitlements] = $this->context($type); $this->requireFeature($profile, 'supplier_profile');
        abort_if($product && $product->medical_supplier_id !== $record->id, 404);
        $limit = $this->portal->limit($profile, 'products_limit');
        abort_if($limit === null, 403, __('An active package is required to manage products.'));
        abort_if(! $product && $limit !== null && $limit > 0 && $record->products()->count() >= $limit, 403, __('You have reached your product limit. Upgrade your package to add more.'));
        $imageLimit = max(0, (int) ($entitlements['additional_images_limit'] ?? 0));
        $rules = ['name' => ['required', 'string', 'max:255'], 'brand' => ['nullable', 'string', 'max:255'], 'model' => ['nullable', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:5000'], 'specifications' => ['nullable', 'string', 'max:5000'], 'availability' => ['nullable', 'string', 'max:255'], 'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']];
        if (! empty($entitlements['categories'])) $rules['medical_supplier_category_id'] = ['nullable', 'integer', Rule::exists('medical_supplier_categories', 'id')->where('is_active', true)];
        if (! empty($entitlements['catalogue'])) { $rules['catalogue'] = ['nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:10240']; $rules['remove_catalogue'] = ['sometimes', 'boolean']; }
        if ($imageLimit > 0 && ! empty($entitlements['product_images'])) {
            $rules['additional_images'] = ['sometimes', 'array', 'max:'.$imageLimit];
            $rules['additional_images.*'] = ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];
            $rules['remove_additional_images'] = ['sometimes', 'array'];
            $rules['remove_additional_images.*'] = ['string', 'max:500'];
        }
        if (! empty($entitlements['featured_products'])) $rules['is_featured'] = ['sometimes', 'boolean'];
        $data = $request->validate($rules);
        if (isset($data['medical_supplier_category_id'])) abort_unless($record->categories()->whereKey($data['medical_supplier_category_id'])->exists(), 422, __('Choose one of your approved categories.'));
        if (empty($entitlements['brands'])) unset($data['brand']);
        $data['slug'] = Str::slug($data['name']);
        $base = $data['slug']; $index = 1;
        while ($record->products()->where('slug', $data['slug'])->when($product, fn ($q) => $q->where('id', '!=', $product->id))->exists()) $data['slug'] = $base . '-' . $index++;
        if ($request->hasFile('image') && ! empty($entitlements['product_images'])) {
            if ($product?->image && str_starts_with($product->image, 'supplier-products/'.$record->id.'/')) Storage::disk('public')->delete($product->image);
            $imagePath = $request->file('image')->storePublicly('supplier-products/'.$record->id, 'public');
        }
        if ($request->hasFile('catalogue') && ! empty($entitlements['catalogue'])) {
            if ($product?->catalogue_path && str_starts_with($product->catalogue_path, 'supplier-catalogues/'.$record->id.'/')) Storage::disk('local')->delete($product->catalogue_path);
            $data['catalogue_path'] = $request->file('catalogue')->store('supplier-catalogues/'.$record->id, 'local');
        } elseif ($request->boolean('remove_catalogue') && $product?->catalogue_path) {
            if (str_starts_with($product->catalogue_path, 'supplier-catalogues/'.$record->id.'/')) Storage::disk('local')->delete($product->catalogue_path);
            $data['catalogue_path'] = null;
        }
        $cataloguePublic = $request->boolean('catalogue_is_public') && ! empty($entitlements['catalogue']);
        $existingImages = $product?->additional_images ?? [];
        $removeImages = array_values(array_intersect($existingImages, $data['remove_additional_images'] ?? []));
        foreach ($removeImages as $path) if (str_starts_with($path, 'supplier-products/'.$record->id.'/')) Storage::disk('public')->delete($path);
        $additionalImages = array_values(array_diff($existingImages, $removeImages));
        $uploads = ! empty($entitlements['product_images']) && $imageLimit > 0 ? $request->file('additional_images', []) : [];
        abort_if(count($additionalImages) + count($uploads) > $imageLimit, 422, __('Your package allows up to :limit additional images per product.', ['limit' => $imageLimit]));
        foreach ($uploads as $upload) $additionalImages[] = $upload->storePublicly('supplier-products/'.$record->id, 'public');
        unset($data['catalogue'], $data['image'], $data['additional_images'], $data['remove_additional_images'], $data['catalogue_is_public'], $data['remove_catalogue']);
        // The uploaded file object is dropped above; keep the stored path (this used to be lost too).
        if (isset($imagePath)) $data['image'] = $imagePath;
        $data['additional_images'] = ! empty($entitlements['product_images']) ? $additionalImages : [];
        $data['catalogue_is_public'] = $cataloguePublic && ! empty($data['catalogue_path'] ?? $product?->catalogue_path);
        $data['is_featured'] = ! empty($entitlements['featured_products']) && $request->boolean('is_featured');
        $data['approval_status'] = 'pending';
        $data['admin_note'] = null;
        $data['is_published'] = false;
        if ($product) $product->update($data); else $record->products()->create($data);
        return back()->with('message', __('Product saved. It remains hidden until admin publishing approval.'));
    }

    public function productImportTemplate(string $type)
    {
        abort_unless($type === 'supplier', 404);
        return response(\App\Services\SupplierProductImport::template(), 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="medfind-products-template.csv"']);
    }

    /** Bulk add/update products from a CSV file; each imported product waits for admin approval. */
    public function importProducts(Request $request, string $type): RedirectResponse
    {
        abort_unless($type === 'supplier', 404);
        [$record, $profile, , $entitlements] = $this->context($type); $this->requireFeature($profile, 'supplier_profile');
        $limit = $this->portal->limit($profile, 'products_limit');
        abort_if($limit === null, 403, __('An active package is required to manage products.'));
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);
        $result = app(\App\Services\SupplierProductImport::class)->import($record, $request->file('file'), $limit, $entitlements);
        $message = __('Import finished: :created added, :updated updated. Imported products stay hidden until an admin approves them.', ['created' => $result['created'], 'updated' => $result['updated']]);
        return back()->with(['message' => $message, 'import_errors' => array_slice($result['errors'], 0, 100)]);
    }

    public function quotations(string $type): View
    {
        abort_unless($type === 'supplier', 404);
        [$record, $profile, $subscription, $entitlements] = $this->context($type);
        $this->requireFeature($profile, 'inquiries');
        $quotes = $record->quotationRequests()->with(['product', 'statusHistory'])->latest()->paginate(20);
        return view('provider.partner-quotations', compact('type', 'record', 'profile', 'subscription', 'entitlements', 'quotes'));
    }

    public function updateQuotation(Request $request, string $type, QuotationRequest $quote): RedirectResponse
    {
        abort_unless($type === 'supplier', 404);
        [$record, $profile] = $this->context($type);
        $this->requireFeature($profile, 'inquiries');
        abort_unless($quote->medical_supplier_id === $record->id, 404);
        $data = $request->validate(['status' => ['required', Rule::in(['new', 'contacted', 'quoted', 'closed'])]]);
        DB::transaction(function () use ($quote, $data, $record) {
            $locked = QuotationRequest::whereKey($quote->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== $data['status']) {
                $locked->update($data);
                $locked->statusHistory()->create(['status' => $data['status'], 'actor_type' => 'supplier', 'actor_id' => $record->id]);
            }
        });
        return back()->with('message', __('Quotation request status updated.'));
    }

    public function downloadCatalogue(string $type, SupplierProduct $product)
    {
        abort_unless($type === 'supplier', 404);
        [$record, $profile] = $this->context($type);
        $this->requireFeature($profile, 'catalogue');
        abort_unless($product->medical_supplier_id === $record->id && $product->catalogue_path, 404);
        abort_unless(Storage::disk('local')->exists($product->catalogue_path), 404);
        return Storage::disk('local')->download($product->catalogue_path);
    }

    public function updateEnquiry(Request $request, string $type, HealthcareInstitutionEnquiry $enquiry): RedirectResponse
    {
        abort_unless($type === 'school', 404);
        [$record, $profile] = $this->context($type);
        $this->requireFeature($profile, 'inquiries');
        abort_unless($enquiry->healthcare_institution_id === $record->id, 404);
        $data = $request->validate(['status' => ['required', Rule::in(['new', 'contacted', 'closed', 'reviewed', 'replied'])]]);
        $enquiry->update($data);
        return back()->with('message', __('Enquiry status updated.'));
    }

    public function deleteProduct(string $type, SupplierProduct $product): RedirectResponse
    {
        abort_unless($type === 'supplier', 404);
        [$record, $profile] = $this->context($type); $this->requireFeature($profile, 'supplier_profile');
        abort_unless($product->medical_supplier_id === $record->id, 404);
        foreach (array_filter(array_merge([$product->image], $product->additional_images ?? [])) as $path) {
            if (str_starts_with($path, 'supplier-products/'.$record->id.'/')) Storage::disk('public')->delete($path);
        }
        if ($product->catalogue_path && str_starts_with($product->catalogue_path, 'supplier-catalogues/'.$record->id.'/')) Storage::disk('local')->delete($product->catalogue_path);
        $product->delete();
        return back()->with('message', __('Product removed.'));
    }

    private function storeImages(Request $request, array $data, array $fields): array
    {
        foreach ($fields as $field => $directory) if ($request->hasFile($field)) $data[$field] = $request->file($field)->storePublicly($directory, 'public');
        return $data;
    }

    private function uniqueSlug($record, string $name): string
    {
        $model = get_class($record);
        $base = Str::slug($name) ?: Str::random(8);
        $slug = $base; $suffix = 1;
        while ($model::where('slug', $slug)->whereKeyNot($record->getKey())->exists()) $slug = $base . '-' . $suffix++;
        return $slug;
    }
}
