<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Doctor\app\Models\ProviderInquiry;
use Modules\Doctor\app\Models\ProviderReview;

class ProviderInboxController extends Controller {
    public function update(\Illuminate\Http\Request $request, string $type, int $inquiry) {
        abort_unless(in_array($type, ['doctor', 'clinic'], true), 404);
        $provider = Auth::guard($type)->user();
        abort_unless($provider, 401);
        $access = app(\Modules\Doctor\app\Services\SubscriptionAccessService::class);
        abort_unless($access->{$type.'Can'}($provider, 'inquiries'), 403);
        $item = ProviderInquiry::where('provider_type', $type)->where('provider_id', $provider->id)->findOrFail($inquiry);
        $item->update($request->validate(['status' => ['required', 'in:new,contacted,closed']]));
        return back()->with('message', __('Inquiry status updated.'));
    }
    public function index(string $type): View {
        abort_unless(in_array($type, ['doctor', 'clinic'], true), 404);
        $provider = Auth::guard($type)->user();
        abort_unless($provider, 403);
        $access = app(\Modules\Doctor\app\Services\SubscriptionAccessService::class);
        $subscription = $type === 'doctor' ? $access->activeDoctorSubscription($provider) : $access->activeClinicSubscription($provider);
        abort_unless($subscription?->hasEntitlement('inquiries'), 403, __('Patient inquiries are not included in your active package.'));
        $inquiries = ProviderInquiry::where('provider_type', $type)->where('provider_id', $provider->id)->latest()->paginate(15, ['*'], 'inquiries');
        $reviews = $subscription->hasEntitlement('reviews') ? ProviderReview::where('provider_type', $type)->where('provider_id', $provider->id)->latest()->take(10)->get() : collect();
        return view('provider.inbox', compact('type', 'inquiries', 'reviews') + [
            'layout' => $type === 'doctor' ? 'doctor.master_layout' : 'clinic.master_layout',
            'contentSection' => $type === 'doctor' ? 'admin-content' : 'clinic-content',
        ]);
    }
}
