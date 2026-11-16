<?php

namespace Modules\Doctor\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Modules\Doctor\app\Models\Doctor;
use Modules\Doctor\app\Models\DoctorSubscription;
use Modules\Doctor\app\Models\Clinic;
use Modules\Doctor\app\Models\ClinicSubscription;

class SubscriptionController extends Controller {

    // ─── Doctor subscriptions ───────────────────────────────────────────────

    public function doctorIndex(Request $request) {
        checkAdminHasPermissionAndThrowException('doctor.view');

        $query = DoctorSubscription::with('doctor')->latest();

        $query->when($request->filled('status'), fn($q) => $q->where('status', $request->status));

        $query->when($request->filled('keyword'), function ($q) use ($request) {
            $q->whereHas('doctor', fn($d) => $d->where('email', 'like', '%' . $request->keyword . '%')
                ->orWhereHas('translations', fn($t) => $t->where('name', 'like', '%' . $request->keyword . '%')));
        });

        $subscriptions = $query->paginate(20)->withQueryString();

        return view('doctor::subscription.doctor-index', compact('subscriptions'));
    }

    public function doctorShow($id) {
        checkAdminHasPermissionAndThrowException('doctor.view');
        $subscription = DoctorSubscription::with('doctor')->findOrFail($id);
        return view('doctor::subscription.doctor-show', compact('subscription'));
    }

    public function doctorApprove(Request $request, $id): RedirectResponse { abort(422, __("Payments are confirmed automatically by Paystack. Manual approval is disabled.")); }

    public function doctorReject(Request $request, $id): RedirectResponse {
        checkAdminHasPermissionAndThrowException('doctor.update');

        $request->validate(['admin_note' => 'required|string|max:500']);

        $subscription = DoctorSubscription::findOrFail($id);
        $subscription->update([
            'status'     => 'rejected',
            'admin_note' => $request->admin_note,
        ]);

        $notification = ['message' => __('Subscription rejected.'), 'alert-type' => 'warning'];
        return redirect()->route('admin.subscriptions.doctor.index')->with($notification);
    }

    // ─── Clinic subscriptions ───────────────────────────────────────────────

    public function clinicIndex(Request $request) {
        checkAdminHasPermissionAndThrowException('doctor.view');

        $query = ClinicSubscription::with('clinic')->latest();

        $query->when($request->filled('status'), fn($q) => $q->where('status', $request->status));

        $query->when($request->filled('keyword'), fn($q) =>
            $q->whereHas('clinic', fn($c) => $c->where('name', 'like', '%' . $request->keyword . '%')
                ->orWhere('email', 'like', '%' . $request->keyword . '%'))
        );

        $subscriptions = $query->paginate(20)->withQueryString();

        return view('doctor::subscription.clinic-index', compact('subscriptions'));
    }

    public function clinicShow($id) {
        checkAdminHasPermissionAndThrowException('doctor.view');
        $subscription = ClinicSubscription::with('clinic')->findOrFail($id);
        return view('doctor::subscription.clinic-show', compact('subscription'));
    }

    public function clinicApprove(Request $request, $id): RedirectResponse { abort(422, __("Payments are confirmed automatically by Paystack. Manual approval is disabled.")); }

    public function clinicReject(Request $request, $id): RedirectResponse {
        checkAdminHasPermissionAndThrowException('doctor.update');

        $request->validate(['admin_note' => 'required|string|max:500']);

        $subscription = ClinicSubscription::findOrFail($id);
        $subscription->update([
            'status'     => 'rejected',
            'admin_note' => $request->admin_note,
        ]);

        $notification = ['message' => __('Subscription rejected.'), 'alert-type' => 'warning'];
        return redirect()->route('admin.subscriptions.clinic.index')->with($notification);
    }
}
