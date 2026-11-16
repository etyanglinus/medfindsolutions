<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Doctor\app\Models\Clinic;
use Modules\Doctor\app\Models\Doctor;
use Modules\Doctor\app\Models\LicensingBody;
use Modules\Doctor\app\Models\ProviderDocument;
use App\Models\ProviderProfile;
use App\Models\ProviderDocumentReview;
use Illuminate\Support\Facades\DB;
use Modules\HealthcareInstitution\app\Models\HealthcareInstitution;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;

class ProviderDocumentController extends Controller {
    public function partnerIndex(string $type): View {
        abort_unless(in_array($type, ['school', 'supplier'], true), 404);
        $provider = Auth::guard($type)->user();
        abort_unless($provider, 403);
        $documents = ProviderDocument::with('reviews')->where('provider_type', $type)->where('provider_id', $provider->id)->latest()->get();
        return view('provider.partner-documents', compact('type', 'provider', 'documents'));
    }

    public function partnerStore(Request $request, string $type): RedirectResponse {
        abort_unless(in_array($type, ['school', 'supplier'], true), 404);
        $provider = Auth::guard($type)->user();
        abort_unless($provider, 403);
        return $this->saveDocument($request, $type, $provider);
    }

    public function index(string $type): View {
        $provider = $this->authenticatedProvider($type);
        $licensingBodies = in_array($type, ['doctor', 'clinic'], true)
            ? ($type === 'doctor' ? LicensingBody::activeDoctorBodies() : LicensingBody::activeFacilityBodies())
            : collect();
        $documents = ProviderDocument::where('provider_type', $type)->where('provider_id', $provider->id)->latest()->get();
        return view('provider.documents', compact('type', 'provider', 'licensingBodies', 'documents') + [
            'layout' => $type === 'doctor' ? 'doctor.master_layout' : 'clinic.master_layout',
            'contentSection' => $type === 'doctor' ? 'admin-content' : 'clinic-content',
        ]);
    }

    public function store(Request $request, string $type): RedirectResponse {
        $provider = $this->authenticatedProvider($type);
        return $this->saveDocument($request, $type, $provider);
    }

    private function saveDocument(Request $request, string $type, $provider): RedirectResponse {
        $data = $request->validate([
            'licensing_body' => ['required', 'string', 'max:255'],
            'registration_number' => ['required', 'string', 'max:100'],
            'verification_body' => ['nullable', 'string', 'max:255'],
            'accreditation_expires_at' => ['nullable', 'date', 'after_or_equal:today'],
            'license' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:10240'],
        ]);
        $path = $request->file('license')->store('provider-documents/' . $type . '/' . $provider->id, 'local');
        unset($data['license']);
        ProviderDocument::create($data + ['provider_type' => $type, 'provider_id' => $provider->id, 'file_path' => $path]);
        return back()->with(['message' => __('License submitted for review.'), 'alert-type' => 'success']);
    }

    public function adminIndex(Request $request): View {
        checkAdminHasPermissionAndThrowException('doctor.view');
        $type = $request->input('provider_type', 'doctor');
        abort_unless(in_array($type, ['doctor', 'clinic', 'school', 'supplier'], true), 404);
        $documents = ProviderDocument::with('reviews')->where('provider_type', $type)->latest()->paginate(20)->withQueryString();
        $model = match ($type) {
            'doctor' => Doctor::class, 'clinic' => Clinic::class,
            'school' => HealthcareInstitution::class, default => MedicalSupplier::class,
        };
        $providers = $model::whereIn('id', $documents->pluck('provider_id'))->get()->keyBy('id');
        return view('admin.provider_documents.index', compact('type', 'documents', 'providers'));
    }

    public function adminDownload(ProviderDocument $document) {
        checkAdminHasPermissionAndThrowException('doctor.view');
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);
        return Storage::disk('local')->download($document->file_path);
    }

    public function adminUpdate(Request $request, ProviderDocument $document): RedirectResponse {
        checkAdminHasPermissionAndThrowException('doctor.update');
        $data = $request->validate(['status' => ['required', 'in:approved,rejected,pending'], 'admin_note' => ['nullable', 'string', 'max:1000']]);
        DB::transaction(function () use ($document, $data) {
            ProviderDocumentReview::create([
                'provider_document_id' => $document->id,
                'admin_id' => Auth::guard('admin')->id(),
                'previous_status' => $document->status,
                'status' => $data['status'],
                'note' => $data['admin_note'] ?? null,
            ]);
            $document->update($data);
        });
        return back()->with(['message' => __('License review updated.'), 'alert-type' => 'success']);
    }

    private function authenticatedProvider(string $type): Doctor|Clinic|HealthcareInstitution|MedicalSupplier {
        abort_unless(in_array($type, ['doctor', 'clinic', 'school', 'supplier'], true), 404);
        $provider = Auth::guard($type)->user();
        abort_unless($provider, 403);
        return $provider;
    }
}
