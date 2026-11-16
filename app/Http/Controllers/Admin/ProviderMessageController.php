<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminProviderMessage;
use App\Traits\GlobalMailTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Doctor\app\Models\Clinic;
use Modules\Doctor\app\Models\Doctor;
use Modules\HealthcareInstitution\app\Models\HealthcareInstitution;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;

class ProviderMessageController extends Controller
{
    use GlobalMailTrait;

    public function index(Request $request)
    {
        checkAdminHasPermissionAndThrowException('doctor.view');
        $type = $request->input('provider_type', 'doctor');
        abort_unless(in_array($type, ['doctor', 'clinic', 'school', 'supplier'], true), 404);
        $providers = $this->providers($type);
        $providerId = $request->integer('provider_id') ?: $providers->first()?->id;
        $messages = $providerId ? AdminProviderMessage::where('provider_type', $type)->where('provider_id', $providerId)->oldest()->get() : collect();
        return view('admin.provider_messages.index', compact('type', 'providers', 'providerId', 'messages'));
    }

    public function store(Request $request): RedirectResponse
    {
        checkAdminHasPermissionAndThrowException('doctor.update');
        $data = $request->validate(['provider_type' => 'required|in:doctor,clinic,school,supplier', 'provider_id' => 'required|integer', 'message' => 'required|string|max:5000']);
        $provider = $this->providerModel($data['provider_type'])::findOrFail($data['provider_id']);
        AdminProviderMessage::create(['provider_type' => $data['provider_type'], 'provider_id' => $provider->id, 'admin_id' => auth('admin')->id(), 'sender_type' => 'admin', 'message' => strip_tags($data['message'])]);
        try {
            $portalUrl = in_array($data['provider_type'], ['school', 'supplier'], true)
                ? route('website.partner.admin-messages.index', ['type' => $data['provider_type']])
                : route($data['provider_type'] . '.admin-messages.index');
            $email = in_array($data['provider_type'], ['school', 'supplier'], true) ? $provider->official_email : $provider->email;
            $this->sendMail($email, __('New notification from MedFind Solutions'), __('You have received a new message from the MedFind Solutions administration team. Please sign in to view and reply.'), [__('View notification') => $portalUrl]);
        } catch (\Throwable $exception) {
            Log::warning('Provider notification email could not be sent: ' . $exception->getMessage());
        }
        return redirect()->route('admin.provider-messages.index', ['provider_type' => $data['provider_type'], 'provider_id' => $provider->id])->with(['message' => __('Message sent and email notification requested.'), 'alert-type' => 'success']);
    }

    private function providers(string $type)
    {
        $model = $this->providerModel($type);
        $emailColumn = in_array($type, ['school', 'supplier'], true) ? 'official_email' : 'email';
        return $model::orderBy('name')->get(['id', 'name', $emailColumn])->map(function ($provider) use ($emailColumn) {
            $provider->email = $provider->{$emailColumn};
            return $provider;
        });
    }

    private function providerModel(string $type): string
    {
        return match ($type) {
            'doctor' => Doctor::class,
            'clinic' => Clinic::class,
            'school' => HealthcareInstitution::class,
            'supplier' => MedicalSupplier::class,
            default => abort(404),
        };
    }
}
