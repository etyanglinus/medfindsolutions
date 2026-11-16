<?php

namespace App\Http\Controllers;

use App\Models\AdminProviderMessage;
use App\Http\Controllers\Provider\PartnerAuthController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProviderAdminMessageController extends Controller
{
    public function doctorIndex()
    {
        return $this->providerIndex('doctor', auth('doctor')->user());
    }
    public function clinicIndex()
    {
        return $this->providerIndex('clinic', auth('clinic')->user());
    }
    public function doctorStore(Request $request): RedirectResponse { return $this->store($request, 'doctor', auth('doctor')->id(), 'doctor.admin-messages.index'); }
    public function clinicStore(Request $request): RedirectResponse { return $this->store($request, 'clinic', auth('clinic')->id(), 'clinic.admin-messages.index'); }

    public function partnerIndex(string $type)
    {
        $definition = PartnerAuthController::definition($type);
        $provider = auth($definition['guard'])->user();
        abort_unless($provider, 403);
        return $this->providerIndex($type, $provider);
    }

    public function partnerStore(Request $request, string $type): RedirectResponse
    {
        $definition = PartnerAuthController::definition($type);
        $provider = auth($definition['guard'])->user();
        abort_unless($provider, 403);
        return $this->store($request, $type, $provider->id, 'website.partner.admin-messages.index', ['type' => $type]);
    }

    private function providerIndex(string $type, $provider)
    {
        abort_unless($provider, 403);
        $messages = AdminProviderMessage::where('provider_type', $type)->where('provider_id', $provider->id)->oldest()->get();
        AdminProviderMessage::where('provider_type', $type)->where('provider_id', $provider->id)->where('sender_type', 'admin')->whereNull('read_at')->update(['read_at' => now()]);
        return view('provider.admin_messages', [
            'type' => $type,
            'messages' => $messages,
            'provider' => $provider,
            'layout' => in_array($type, ['doctor', 'clinic'], true) ? $type . '.master_layout' : 'layouts.patient.layout',
            'contentSection' => $type === 'doctor' ? 'admin-content' : ($type === 'clinic' ? 'clinic-content' : 'patient-content'),
            'dashboardRoute' => in_array($type, ['doctor', 'clinic'], true) ? $type . '.dashboard' : 'website.partner.dashboard',
            'dashboardRouteParameters' => in_array($type, ['school', 'supplier'], true) ? ['type' => $type] : [],
            'storeRoute' => in_array($type, ['school', 'supplier'], true) ? 'website.partner.admin-messages.store' : $type . '.admin-messages.store',
            'storeRouteParameters' => in_array($type, ['school', 'supplier'], true) ? ['type' => $type] : [],
        ]);
    }

    private function store(Request $request, string $type, int $providerId, string $route, array $routeParameters = []): RedirectResponse
    {
        $request->validate(['message' => 'required|string|max:5000']);
        AdminProviderMessage::create(['provider_type' => $type, 'provider_id' => $providerId, 'sender_type' => 'provider', 'message' => strip_tags($request->message)]);
        return redirect()->route($route, $routeParameters)->with(['message' => __('Reply sent to the administration team.'), 'alert-type' => 'success']);
    }
}
