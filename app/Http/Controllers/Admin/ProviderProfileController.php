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
        $profiles = ProviderProfile::query()
            ->whereIn('provider_type', $types)
            ->when($request->input('status') === 'pending', fn ($query) => $query->whereIn('status', ['draft', 'pending_approval', 'changes_required']))
            ->with(['provider', 'subscriptions' => fn ($query) => $query->latest(), 'provider.documents'])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $title = $type === 'school' ? __('Schools') : ($type === 'supplier' ? __('Suppliers') : __('Schools and Suppliers'));
        return view('admin.provider_profiles.index', compact('profiles', 'type', 'title'));
    }

    public function approve(ProviderProfile $profile): RedirectResponse
    {
        $this->authorizeUpdate($profile);
        abort_unless(in_array($profile->provider_type, [ProviderProfile::TYPE_SCHOOL, ProviderProfile::TYPE_SUPPLIER], true), 404);
        $profile->update(['status' => ProviderProfile::STATUS_APPROVED]);
        return back()->with(['message' => __('Provider approved. It can be published after verification.'), 'alert-type' => 'success']);
    }

    public function verify(ProviderProfile $profile): RedirectResponse
    {
        $this->authorizeUpdate($profile);
        abort_unless(in_array($profile->provider_type, [ProviderProfile::TYPE_SCHOOL, ProviderProfile::TYPE_SUPPLIER], true), 404);
        abort_unless(app(\App\Services\LicenceVerification::class)->hasValidLicence($profile->provider_type, $profile->provider_id), 422, __('Approve an unexpired licence or accreditation document (Provider Documents) before verifying this provider.'));
        $profile->update(['verification_status' => ProviderProfile::VERIFICATION_VERIFIED, 'verified_at' => now()]);
        return back()->with(['message' => __('Provider verification recorded.'), 'alert-type' => 'success']);
    }

    public function publish(ProviderProfile $profile): RedirectResponse
    {
        $this->authorizeUpdate($profile);
        abort_unless($profile->provider && app(\App\Services\ProviderOnboardingService::class)->status($profile->provider, $profile->provider_type)['complete'], 422, __('The client must complete their profile, photo and verification document upload before publication.'));
        abort_unless($profile->status === ProviderProfile::STATUS_APPROVED, 422, __('Approve the provider before publishing.'));
        abort_unless($profile->verification_status === ProviderProfile::VERIFICATION_VERIFIED, 422, __('Verify the provider before publishing.'));
        abort_unless(app(\App\Services\LicenceVerification::class)->hasValidLicence($profile->provider_type, $profile->provider_id), 422, __('The provider has no approved, unexpired licence. Approve a current document before publishing.'));
        $profile->update(['status' => ProviderProfile::STATUS_PUBLISHED, 'published_at' => now()]);
        return back()->with(['message' => __('Provider published.'), 'alert-type' => 'success']);
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
