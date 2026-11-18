<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProviderProfileController extends Controller
{
    public function index(Request $request): View
    {
        $types = array_values(array_filter(['school', 'supplier'], fn ($type) => checkAdminHasPermission("$type.view")));
        if (!$types) checkAdminHasPermissionAndThrowException('school.view');
        $type = $request->input('type');
        if ($type !== null) {
            abort_unless(in_array($type, ['school', 'supplier']), 404);
            checkAdminHasPermissionAndThrowException($type.'.view');
            $types = [$type];
        }
        $search = trim((string) $request->input('q', ''));
        $filter = $request->input('filter');
        $base = ProviderProfile::query()->whereIn('provider_type', $types)->whereHas('provider');
        $listedIds = collect($types)->flatMap(fn ($t) => app(\App\Services\PublicDirectory::class)->query($t)->pluck('provider_profile_id'))->filter()->values();
        $counts = [
            'all' => (clone $base)->count(),
            'listed' => (clone $base)->whereIn('id', $listedIds)->count(),
            'review' => (clone $base)->whereIn('status', ['draft', 'pending_approval', 'changes_required', 'approved', 'pending_payment'])->count(),
            'suspended' => (clone $base)->where('status', ProviderProfile::STATUS_SUSPENDED)->count(),
        ];
        $profiles = (clone $base)
            ->when($search !== '', fn ($q) => $q->whereHasMorph('provider', ['school', 'supplier'], fn ($p) => $p->where('name', 'like', "%{$search}%")->orWhere('official_email', 'like', "%{$search}%")))
            ->when($filter === 'listed', fn ($q) => $q->whereIn('id', $listedIds))
            ->when($filter === 'hidden', fn ($q) => $q->whereNotIn('id', $listedIds))
            ->when($filter === 'review' || $request->input('status') === 'pending', fn ($q) => $q->whereIn('status', ['draft', 'pending_approval', 'changes_required', 'approved', 'pending_payment']))
            ->when($filter === 'suspended', fn ($q) => $q->where('status', ProviderProfile::STATUS_SUSPENDED))
            ->with(['provider', 'subscriptions' => fn ($query) => $query->latest(), 'provider.documents'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $title = $type === 'school' ? __('Schools') : ($type === 'supplier' ? __('Suppliers') : __('Schools and Suppliers'));
        return view('admin.provider_profiles.index', compact('profiles', 'type', 'title', 'counts', 'search', 'filter'));
    }

    public function approve(ProviderProfile $profile): RedirectResponse
    {
        $this->authorizeUpdate($profile);
        $profile->update(['status' => ProviderProfile::STATUS_APPROVED]);
        return back()->with(['message' => __('Provider approved. It can be published after verification.'), 'alert-type' => 'success']);
    }

    public function verify(ProviderProfile $profile): RedirectResponse
    {
        $this->authorizeUpdate($profile);
        if (!app(\App\Services\LicenceVerification::class)->hasValidLicence($profile->provider_type, $profile->provider_id)) {
            return back()->with(['message' => __('Approve an unexpired licence or accreditation document (Provider Documents) before verifying this provider.'), 'alert-type' => 'error']);
        }
        $profile->update(['verification_status' => ProviderProfile::VERIFICATION_VERIFIED, 'verified_at' => now()]);
        return back()->with(['message' => __('Provider verification recorded.'), 'alert-type' => 'success']);
    }

    /**
     * One click: approve, verify and publish. Refused with a plain message (never an error page)
     * when details are missing unless an admin explicitly allows an incomplete listing.
     * An approved, current licence is always required.
     */
    public function publish(Request $request, ProviderProfile $profile): RedirectResponse
    {
        $this->authorizeUpdate($profile);
        $request->validate(['allow_incomplete_listing' => ['sometimes', 'boolean']]);
        $allowIncomplete = $request->boolean('allow_incomplete_listing');
        if ($allowIncomplete && $profile->status === ProviderProfile::STATUS_SUSPENDED) {
            return back()->with(['message' => __('This provider is suspended.'), 'alert-type' => 'error']);
        }

        $name = $profile->provider?->name;
        $onboarding = $profile->provider ? app(\App\Services\ProviderOnboardingService::class)->status($profile->provider, $profile->provider_type) : null;
        if (!$onboarding || !$onboarding['document'] || (!$allowIncomplete && !$onboarding['complete'])) {
            $missing = $onboarding ? implode(', ', array_merge($onboarding['missing'], $onboarding['photo'] ? [] : [__('logo')], $onboarding['document'] ? [] : [__('licence document')])) : '';
            return back()->with(['message' => __(':name cannot be published yet: the profile is missing :missing.', ['name' => $name, 'missing' => $missing ?: __('required details')]), 'alert-type' => 'error']);
        }
        if (!app(\App\Services\LicenceVerification::class)->hasValidLicence($profile->provider_type, $profile->provider_id)) {
            return back()->with(['message' => __(':name cannot be published until their licence is approved. Open "Review licence", approve the document, then click Publish again.', ['name' => $name]), 'alert-type' => 'error']);
        }
        $profile->update(['status' => ProviderProfile::STATUS_PUBLISHED, 'published_at' => $profile->published_at ?? now(),
            'verification_status' => ProviderProfile::VERIFICATION_VERIFIED, 'verified_at' => $profile->verified_at ?? now()]);
        \App\Services\PublicDirectory::forget();
        $live = app(\App\Services\PublicDirectory::class)->query($profile->provider_type)->whereKey($profile->provider_id)->exists();
        return back()->with(['message' => $live ? __(':name is published and visible on the website.', ['name' => $name]) : __(':name is published, but stays hidden until they have a paid, current plan. Use "Activate plan" if they paid another way.', ['name' => $name]), 'alert-type' => $live ? 'success' : 'warning']);
    }

    public function unpublish(ProviderProfile $profile): RedirectResponse
    {
        $this->authorizeUpdate($profile);
        $profile->update(['status' => ProviderProfile::STATUS_APPROVED, 'published_at' => null]);
        \App\Services\PublicDirectory::forget();
        return back()->with(['message' => __(':name has been taken off the website. Their account and plan are unchanged.', ['name' => $profile->provider?->name]), 'alert-type' => 'success']);
    }

    public function requestChanges(Request $request, ProviderProfile $profile): RedirectResponse
    {
        $this->authorizeUpdate($profile);
        $request->validate(['admin_note' => ['required', 'string', 'max:2000']]);
        $profile->update(['status' => ProviderProfile::STATUS_CHANGES_REQUIRED]);
        return back()->with(['message' => __('Changes requested from provider.'), 'alert-type' => 'success']);
    }

    public function suspend(ProviderProfile $profile): RedirectResponse
    {
        $this->authorizeUpdate($profile);
        $profile->update(['status' => ProviderProfile::STATUS_SUSPENDED]);
        \App\Services\PublicDirectory::forget();
        return back()->with(['message' => __('Provider suspended.'), 'alert-type' => 'success']);
    }

    private function authorizeUpdate(ProviderProfile $profile): void
    {
        abort_unless(in_array($profile->provider_type, ['school', 'supplier'], true), 404);
        checkAdminHasPermissionAndThrowException($profile->provider_type.'.update');
    }

    public function destroy(ProviderProfile $profile): RedirectResponse
    {
        abort_unless(in_array($profile->provider_type, ['school', 'supplier']), 404);
        checkAdminHasPermissionAndThrowException($profile->provider_type.'.delete');
        abort_unless($profile->provider, 404);
        app(\App\Services\ClientDeletionService::class)->delete($profile->provider, $profile->provider_type);
        return redirect()->route('admin.provider-profiles.index', ['type' => $profile->provider_type])
            ->with(['message' => __('Account deleted. Payment and creation history retained.'), 'alert-type' => 'success']);
    }
}
