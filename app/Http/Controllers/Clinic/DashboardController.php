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
        try {
            $clinic->loadMissing('services');
        } catch (\Throwable) {
            // services pivot may not exist yet — dashboard degrades gracefully
        }
        $subscription = app(SubscriptionAccessService::class)->activeClinicSubscription($clinic);

        $viewDays = collect(range(29, 0))->map(fn ($offset) => now()->subDays($offset)->toDateString());
        $profileViews = ProfileView::query()->selectRaw('viewed_on, COUNT(*) as total')
            ->where('profile_type', 'clinic')->where('profile_id', $clinic->id)
            ->whereBetween('viewed_on', [$viewDays->first(), $viewDays->last()])
            ->groupBy('viewed_on')->pluck('total', 'viewed_on');
        $profileViewsTotal  = ProfileView::where('profile_type', 'clinic')->where('profile_id', $clinic->id)->count();
        $profileViews30Days = $profileViews->sum();
        $profileViewLabels  = $viewDays->map(fn ($day) => Carbon::parse($day)->format('d M'))->values();
        $profileViewData    = $viewDays->map(fn ($day) => (int) ($profileViews[$day] ?? 0))->values();

        return view('clinic.dashboard', compact(
            'clinic', 'subscription',
            'profileViewsTotal', 'profileViews30Days',
            'profileViewLabels', 'profileViewData'
        ));
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
