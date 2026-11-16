<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Doctor\app\Models\ClinicBranch;
use Modules\Doctor\app\Models\ClinicGalleryImage;
use Modules\Doctor\app\Services\SubscriptionAccessService;

class ContentController extends Controller {
    public function index(): View {
        $clinic = Auth::guard('clinic')->user();
        return view('clinic.content', ['clinic' => $clinic, 'subscription' => app(SubscriptionAccessService::class)->activeClinicSubscription($clinic)]);
    }

    public function storeGallery(Request $request): RedirectResponse {
        $clinic = Auth::guard('clinic')->user();
        $subscription = app(SubscriptionAccessService::class)->activeClinicSubscription($clinic);
        abort_unless($subscription?->isActive() && $subscription->hasEntitlement('gallery_limit'), 403);
        $request->validate(['image' => ['required', 'image', 'max:5120'], 'caption' => ['nullable', 'string', 'max:255']]);
        $limit = (int) $subscription->entitlement('gallery_limit', 0);
        abort_if($limit < 1 || $clinic->galleryImages()->count() >= $limit, 422, __('Your gallery image limit has been reached.'));
        $path = $request->file('image')->store('clinic-gallery/' . $clinic->id, 'public');
        $clinic->galleryImages()->create(['image_path' => $path, 'caption' => $request->caption]);
        return back()->with(['message' => __('Gallery image added.'), 'alert-type' => 'success']);
    }

    public function deleteGallery(ClinicGalleryImage $image): RedirectResponse {
        abort_unless($image->clinic_id === Auth::guard('clinic')->id(), 403);
        abort_unless(app(SubscriptionAccessService::class)->clinicCan(Auth::guard('clinic')->user(), 'gallery_limit'), 403);
        Storage::disk('public')->delete($image->image_path);
        $image->delete();
        return back()->with(['message' => __('Gallery image removed.'), 'alert-type' => 'success']);
    }

    public function storeBranch(Request $request): RedirectResponse {
        $clinic = Auth::guard('clinic')->user();
        abort_unless(app(SubscriptionAccessService::class)->clinicCan($clinic, 'branches'), 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'address' => ['required', 'string', 'max:500'], 'phone' => ['nullable', 'string', 'max:30']]);
        $clinic->branches()->create($data);
        return back()->with(['message' => __('Branch added.'), 'alert-type' => 'success']);
    }

    public function deleteBranch(ClinicBranch $branch): RedirectResponse {
        abort_unless($branch->clinic_id === Auth::guard('clinic')->id(), 403);
        abort_unless(app(SubscriptionAccessService::class)->clinicCan(Auth::guard('clinic')->user(), 'branches'), 403);
        $branch->delete();
        return back()->with(['message' => __('Branch removed.'), 'alert-type' => 'success']);
    }
}
