<?php

namespace Modules\Doctor\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Doctor\app\Models\SubscriptionPackage;
use Modules\Doctor\app\Models\SubscriptionPaymentSetting;
use Modules\Doctor\app\Services\SubscriptionPlanService;

class SubscriptionPackageController extends Controller
{
    public function index()
    {
        checkAdminHasPermissionAndThrowException('doctor.view');
        $packages = SubscriptionPackage::orderBy('provider_type')->orderBy('price')->get();
        return view('doctor::subscription.package-index', compact('packages'));
    }

    public function create() { return view('doctor::subscription.package-form', ['package' => new SubscriptionPackage()]); }

    public function store(Request $request): RedirectResponse
    {
        SubscriptionPackage::create($this->validated($request));
        return redirect()->route('admin.subscriptions.packages.index')->with(['message' => __('Plan created.'), 'alert-type' => 'success']);
    }

    public function edit(SubscriptionPackage $package) { return view('doctor::subscription.package-form', compact('package')); }

    public function update(Request $request, SubscriptionPackage $package): RedirectResponse
    {
        $package->update($this->validated($request, $package));
        return redirect()->route('admin.subscriptions.packages.index')->with(['message' => __('Plan updated.'), 'alert-type' => 'success']);
    }

    public function destroy(SubscriptionPackage $package): RedirectResponse
    {
        $package->delete();
        return redirect()->route('admin.subscriptions.packages.index')->with(['message' => __('Plan deleted.'), 'alert-type' => 'success']);
    }

    /** Settings for manual M-Pesa paybill payments (e.g. the Equity Bank paybill). */
    public function paymentDetails()
    {
        checkAdminHasPermissionAndThrowException('setting.view');
        $details = \Modules\Doctor\app\Models\SubscriptionPaymentSetting::query()->first();
        return view('doctor::subscription.payment-details', compact('details'));
    }

    public function updatePaymentDetails(Request $request): RedirectResponse
    {
        checkAdminHasPermissionAndThrowException('setting.update');
        $data = $request->validate([
            'is_active' => ['nullable', 'boolean'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'paybill_number' => ['required_if:is_active,1', 'nullable', 'regex:/^[0-9]{5,7}$/'],
            'account_number' => ['required_if:is_active,1', 'nullable', 'string', 'max:60'],
            'account_name' => ['nullable', 'string', 'max:120'],
            'instructions' => ['nullable', 'string', 'max:2000'],
        ], ['paybill_number.regex' => __('A paybill number is 5 to 7 digits, for example 247247.')]);
        $data['is_active'] = $request->boolean('is_active');
        $details = \Modules\Doctor\app\Models\SubscriptionPaymentSetting::query()->first() ?? new \Modules\Doctor\app\Models\SubscriptionPaymentSetting();
        $details->fill($data)->save();
        return back()->with(['message' => $data['is_active'] ? __('Paybill payments are on. Providers now see the paybill option when they pay.') : __('Paybill settings saved. The option is hidden from providers while it is off.'), 'alert-type' => 'success']);
    }

    private function validated(Request $request, ?SubscriptionPackage $package = null): array
    {
        $data = $request->validate([
            'provider_type' => ['required', Rule::in(['doctor', 'clinic', 'school', 'supplier'])],
            'code' => ['required', 'alpha_dash', 'max:100', Rule::unique('subscription_packages')->where(fn ($q) => $q->where('provider_type', $request->provider_type))->ignore($package?->id)],
            'name' => 'required|string|max:255', 'tagline' => 'nullable|string|max:255', 'price' => 'required|numeric|min:0',
            'duration_months' => 'required|integer|min:1|max:120', 'features' => 'nullable|string', 'entitlements' => 'nullable|json', 'is_active' => 'nullable|boolean',
        ]);
        $rawEntitlements = json_decode($data['entitlements'] ?: '{}', true);
        if (! is_array($rawEntitlements)) throw \Illuminate\Validation\ValidationException::withMessages(['entitlements' => __('Entitlements must be a JSON object.')]);
        $allowed = app(SubscriptionPlanService::class)->supportedEntitlements($data['provider_type'], array_fill_keys([
            'search_visibility','verified_badge','homepage_rotation','services_limit','highlights_limit','cover_photo','whatsapp','description','insurance','maps','top_clinic_badge','services','analytics','telemedicine','emergency','reviews','inquiries','consultation_fee','top_specialist_badge','gallery_limit','equipment','operating_hours','branches','featured_services','services_limit','institution_profile','photos','courses_limit','featured_courses','intake_promotion','location_map','application_information','campus_facilities','practical_training','accommodation','international_pathways','featured_intake_campaign','promotional_banner','products_limit','catalogue','gallery','gallery_limit','supplier_profile','categories','supplier_types','product_images','additional_images_limit','website','areas_served','brands','social_links','featured_products','featured_supplier'
        ], true));
        $unknown = array_diff(array_keys($rawEntitlements), array_keys($allowed));
        if ($unknown) throw \Illuminate\Validation\ValidationException::withMessages(['entitlements' => __('Unsupported entitlements for this provider type: :keys', ['keys' => implode(', ', $unknown)])]);
        foreach ($rawEntitlements as $key => $value) {
            if (str_ends_with($key, '_limit')) {
                $limit = filter_var($value, FILTER_VALIDATE_INT);
                if ($limit === false || $limit < 0) throw \Illuminate\Validation\ValidationException::withMessages(['entitlements' => __(':key must be a whole number greater than or equal to zero.', ['key' => $key])]);
                $rawEntitlements[$key] = $limit;
            } elseif (! is_bool($value)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['entitlements' => __(':key must be true or false.', ['key' => $key])]);
            }
        }
        $data['entitlements'] = app(SubscriptionPlanService::class)->supportedEntitlements($data['provider_type'], $rawEntitlements);
        $data['features'] = collect(preg_split('/\r\n|\r|\n/', $data['features'] ?? ''))->map(fn ($feature) => trim($feature))->filter()->values()->all();
        $data['is_active'] = $request->boolean('is_active');
        return $data;
    }
}
