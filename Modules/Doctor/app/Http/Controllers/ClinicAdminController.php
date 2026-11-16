<?php

namespace Modules\Doctor\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Doctor\app\Models\Clinic;
use Modules\Doctor\app\Models\SubscriptionPackage;

class ClinicAdminController extends Controller
{
    public function index(Request $request)
    {
        checkAdminHasPermissionAndThrowException('facility.view');
        $clinics = Clinic::query()->with('latestSubscription')
            ->when($request->filled('keyword'), fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%' . $request->keyword . '%')->orWhere('email', 'like', '%' . $request->keyword . '%')->orWhere('specialty', 'like', '%' . $request->keyword . '%')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('plan_code'), fn ($query) => $query->whereHas('subscriptions', fn ($subscription) => $subscription->where('plan_code', $request->plan_code)))
            ->latest()->paginate(20)->withQueryString();
        $plans = SubscriptionPackage::where('provider_type', 'clinic')->where('is_active', true)->orderBy('price')->get();
        return view('doctor::clinic_admin.index', compact('clinics', 'plans'));
    }

    public function edit(Clinic $clinicListing)
    {
        checkAdminHasPermissionAndThrowException('facility.edit');
        return view('doctor::clinic_admin.edit', ['clinic' => $clinicListing]);
    }

    public function update(Request $request, Clinic $clinicListing)
    {
        checkAdminHasPermissionAndThrowException('facility.update');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'specialty' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('clinics')->ignore($clinicListing->id)],
            'phone' => ['required', 'string', 'max:50'], 'location' => ['nullable', 'string', 'max:255'],
            'consultation_fee' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'], 'insurance_accepted' => ['nullable', 'string', 'max:2000'],
            'operating_hours' => ['nullable', 'string', 'max:2000'], 'status' => ['nullable', 'boolean'],
            'emergency_available' => ['nullable', 'boolean'], 'reviews_enabled' => ['nullable', 'boolean'],
        ]);
        $data['status'] = $request->boolean('status');
        $data['location'] = 'Nairobi';
        $clinicListing->update($data);
        return redirect()->route('admin.clinic-listing.edit', $clinicListing)->with(['message' => __('Facility updated.'), 'alert-type' => 'success']);
    }

    public function destroy(Clinic $clinicListing)
    {
        checkAdminHasPermissionAndThrowException('facility.delete');
        app(\App\Services\ClientDeletionService::class)->delete($clinicListing, 'clinic');
        return redirect()->route('admin.clinic-listing.index')->with(['message' => __('Facility deleted.'), 'alert-type' => 'success']);
    }
}
