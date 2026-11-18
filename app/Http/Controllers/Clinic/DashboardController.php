<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Modules\Doctor\app\Models\Clinic;
use Modules\Doctor\app\Models\ClinicSubscription;
use Modules\Doctor\app\Services\SubscriptionAccessService;
use App\Models\ProfileView;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Doctor\app\Models\Service;

class DashboardController extends Controller {

    private function clinic(): Clinic {
        return Auth::guard('clinic')->user();
    }

    public function index(): View {
        $clinic = $this->clinic();
        $subscription = app(SubscriptionAccessService::class)->activeClinicSubscription($clinic);
        $days = in_array((int) request('days', 30), [7, 30, 90], true) ? (int) request('days', 30) : 30;
        $insights = app(\App\Services\ListingInsightsService::class)->summary('clinic', $clinic, $days);

        // Same metrics for the period before, for the "vs previous" change on each figure.
        $previous = \App\Models\ListingEvent::where('provider_type', 'clinic')->where('provider_id', $clinic->id)
            ->whereBetween('day', [now()->subDays(2 * $days - 1)->toDateString(), now()->subDays($days)->toDateString()])
            ->selectRaw('event, COUNT(*) as total')->groupBy('event')->pluck('total', 'event');
        $inquiries = \Modules\Doctor\app\Models\ProviderInquiry::where('provider_type', 'clinic')->where('provider_id', $clinic->id);
        $enquiriesNow = (clone $inquiries)->where('created_at', '>=', now()->subDays($days - 1)->startOfDay())->count();
        $enquiriesBefore = (clone $inquiries)->whereBetween('created_at', [now()->subDays(2 * $days - 1)->startOfDay(), now()->subDays($days - 1)->startOfDay()])->count();
        $contacts = fn ($set) => (int) (($set['phone_click'] ?? 0) + ($set['whatsapp_click'] ?? 0) + ($set['website_click'] ?? 0));
        $kpis = [
            ['label' => __('Profile views'), 'icon' => 'fa-eye', 'tone' => 'blue', 'now' => $insights['totals']['profile_view'] ?? 0, 'before' => (int) ($previous['profile_view'] ?? 0)],
            ['label' => __('Search appearances'), 'icon' => 'fa-search', 'tone' => 'violet', 'now' => $insights['totals']['search_appearance'] ?? 0, 'before' => (int) ($previous['search_appearance'] ?? 0)],
            ['label' => __('Calls, WhatsApp & website clicks'), 'icon' => 'fa-phone-alt', 'tone' => 'green', 'now' => $contacts($insights['totals']), 'before' => $contacts($previous)],
            ['label' => __('Enquiries'), 'icon' => 'fa-envelope-open-text', 'tone' => 'amber', 'now' => $enquiriesNow, 'before' => $enquiriesBefore],
        ];
        $chart = [
            'labels' => collect($insights['labels'])->map(fn ($d) => Carbon::parse($d)->format('d M'))->values(),
            'views' => $insights['series']['profile_view'] ?? [],
            'contacts' => collect($insights['series']['phone_click'] ?? [])->map(fn ($v, $i) => $v + ($insights['series']['whatsapp_click'][$i] ?? 0) + ($insights['series']['website_click'][$i] ?? 0))->values(),
        ];
        $recentInquiries = (clone $inquiries)->latest()->take(4)->get();
        $newInquiries = (clone $inquiries)->where('status', 'new')->count();

        // Profile strength: what patients see on the public page.
        $galleryCount = method_exists($clinic, 'galleryImages') ? $clinic->galleryImages()->count() : 0;
        $servicesCount = \Illuminate\Support\Facades\Schema::hasTable('clinic_service') ? $clinic->services()->count() : 0;
        $checklist = [
            ['done' => filled($clinic->logo), 'label' => __('Logo or photo'), 'url' => route('clinic.edit-profile')],
            ['done' => mb_strlen(trim(strip_tags((string) $clinic->description))) >= 80, 'label' => __('About text of at least two sentences'), 'url' => route('clinic.edit-profile')],
            ['done' => filled($clinic->operating_hours), 'label' => __('Opening hours'), 'url' => route('clinic.edit-profile')],
            ['done' => $servicesCount > 0 || filled($clinic->featured_services), 'label' => __('Services offered'), 'url' => route('clinic.edit-profile')],
            ['done' => filled($clinic->insurance_accepted), 'label' => __('Insurance accepted'), 'url' => route('clinic.edit-profile')],
            ['done' => $galleryCount > 0, 'label' => __('Gallery photos'), 'url' => route('clinic.content.index')],
            ['done' => app(\App\Services\LicenceVerification::class)->hasValidLicence('clinic', $clinic->id), 'label' => __('Licence approved by MedFind'), 'url' => route('clinic.documents.index')],
        ];
        $strength = (int) round(100 * collect($checklist)->where('done', true)->count() / count($checklist));
        $listing = app(\App\Services\PublicDirectory::class)->status('clinic', $clinic);
        $daysLeft = $subscription?->expires_at ? max(0, (int) ceil(now()->diffInHours($subscription->expires_at, false) / 24)) : null;
        $planDays = $subscription?->approved_at && $subscription?->expires_at ? max(1, $subscription->approved_at->diffInDays($subscription->expires_at)) : null;

        return view('clinic.dashboard', compact('clinic', 'subscription', 'days', 'insights', 'kpis', 'chart', 'recentInquiries', 'newInquiries', 'checklist', 'strength', 'listing', 'daysLeft', 'planDays'));
    }

    public function subscriptionStatus(): View {
        $clinic       = $this->clinic();
        $subscription = $clinic->latestSubscription;
        $activeSubscription = app(SubscriptionAccessService::class)->activeClinicSubscription($clinic);
        $payments = $clinic->subscriptions()->latest()->paginate(10);
        $packages = app(\Modules\Doctor\app\Services\SubscriptionPlanService::class)->for('clinic');
        return view('clinic.subscription-status', compact('clinic', 'subscription', 'activeSubscription', 'payments', 'packages'));
    }

    public function editProfile(): View {
        $clinic = $this->clinic();
        $subscription = app(SubscriptionAccessService::class)->activeClinicSubscription($clinic);
        $services = Service::active();
        return view('clinic.edit-profile', compact('clinic', 'subscription', 'services'));
    }

    public function updateProfile(Request $request) {
        $clinic = $this->clinic();
        $subscription = app(SubscriptionAccessService::class)->activeClinicSubscription($clinic);

        if (! $subscription?->isActive()) {
            return redirect()->route('clinic.subscription.status')
                ->with(['message' => __('An active subscription is required to update this profile.'), 'alert-type' => 'error']);
        }

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'phone'    => 'required|string|max:30',
            'whatsapp' => 'nullable|string|max:30',
            'specialty'=> 'required|string|max:255',
            'years_of_experience' => 'required|string|max:255',
            'consultation_fee' => 'required|numeric|min:0',
            'licensing_body' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'description' => 'required|string|max:5000',
            'address' => 'required|string|max:5000',
            'educations' => 'required|string|max:10000',
            'experience' => 'required|string|max:10000',
            'qualifications' => 'required|string|max:10000',
            'seo_title' => 'nullable|string|max:1000',
            'seo_description' => 'nullable|string|max:2000',
            'equipment' => 'nullable|string|max:3000',
            'featured_services' => 'nullable|string|max:2000',
            'operating_hours' => 'nullable|string|max:255',
            'google_maps_link' => 'nullable|url|max:500',
            'insurance_accepted' => 'nullable|string|max:2000',
            'emergency_available' => 'nullable|boolean',
            'reviews_enabled' => 'nullable|boolean',
            'services' => 'nullable|array',
            'services.*' => 'integer|distinct|exists:services,id',
            'logo'     => ($clinic->logo ? 'nullable' : 'required').'|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data['emergency_available'] = $request->boolean('emergency_available');
        $data['reviews_enabled'] = $request->boolean('reviews_enabled');
        if ($request->hasFile('logo')) {
            $clinic->logo = uploadAndOptimizeImage(
                file: $request->logo,
                oldFile: $clinic->logo,
            );
        }

        $restrictedFields = [
            'whatsapp' => 'whatsapp',
            'insurance_accepted' => 'insurance',
            'google_maps_link' => 'maps',
            'operating_hours' => 'operating_hours',
            'equipment' => 'equipment',
            'featured_services' => 'featured_services',
            'emergency_available' => 'emergency',
            'reviews_enabled' => 'reviews',
        ];
        foreach ($restrictedFields as $field => $entitlement) {
            if (! $subscription->hasEntitlement($entitlement)) {
                unset($data[$field]);
            }
        }

        $submittedServices = $request->input('services', []);
        unset($data['services']);
        unset($data['logo']);
        $data['location'] = 'Nairobi';
        DB::transaction(function () use ($clinic, $data, $subscription, $submittedServices) {
            if (isset($data['name']) && $data['name'] !== $clinic->name) {
                $base = \Illuminate\Support\Str::slug($data['name']); $slug = $base; $n = 1;
                while (Clinic::where('slug', $slug)->whereKeyNot($clinic->id)->exists()) $slug = $base . '-' . $n++;
                $data['slug'] = $slug;
            }
            $clinic->update($data);
            if ($subscription->hasEntitlement('services_limit')) {
                $ids = collect($submittedServices)->map(fn ($id) => (int) $id)->unique()->take((int) $subscription->entitlement('services_limit'))->all();
                $clinic->services()->sync($ids);
            }
        });

        return redirect()->route('clinic.edit-profile')
            ->with(['message' => __('Profile updated successfully.'), 'alert-type' => 'success']);
    }

    public function changePassword(): View {
        return view('clinic.change-password', ['clinic' => $this->clinic()]);
    }

    public function updatePassword(Request $request) {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:4|confirmed',
        ], [
            'current_password.required' => __('Current password is required'),
            'password.required'         => __('Password is required'),
            'password.min'              => __('Password must be at least 4 characters'),
            'password.confirmed'        => __('Confirm password does not match'),
        ]);

        $clinic = $this->clinic();

        if (! Hash::check($request->current_password, $clinic->password)) {
            return redirect()->back()
                ->with(['message' => __('Current password does not match.'), 'alert-type' => 'error']);
        }

        $clinic->update(['password' => Hash::make($request->password)]);

        return redirect()->back()
            ->with(['message' => __('Password changed successfully.'), 'alert-type' => 'success']);
    }
}
