<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PartnerPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\HealthcareInstitution\app\Models\HealthcareCourse;

/**
 * Admin review of courses schools submit from their portal. Only approved courses appear on the
 * public site; a rejection or change request carries a note the school sees on its dashboard.
 */
class SchoolCourseReviewController extends Controller
{
    public function index(Request $request): View
    {
        checkAdminHasPermissionAndThrowException('school.view');
        $status = $request->input('status');
        $courses = HealthcareCourse::query()->whereHas('institution')->with('institution')
            ->when($status, fn ($q) => $q->where('approval_status', $status))
            ->when(!$status, fn ($q) => $q->whereIn('approval_status', ['pending', 'changes_required']))
            ->latest('updated_at')->paginate(30)->withQueryString();
        $counts = HealthcareCourse::query()->whereHas('institution')->selectRaw('approval_status, COUNT(*) as total')->groupBy('approval_status')->pluck('total', 'approval_status');
        return view('admin.school_courses.index', compact('courses', 'counts', 'status'));
    }

    public function update(Request $request, HealthcareCourse $course): RedirectResponse
    {
        checkAdminHasPermissionAndThrowException('school.update');
        $data = $request->validate([
            'approval_status' => ['required', Rule::in(['approved', 'rejected', 'changes_required'])],
            'admin_note' => ['nullable', 'string', 'max:1000', Rule::requiredIf(fn () => $request->input('approval_status') !== 'approved')],
        ]);
        $school = $course->institution()->with('providerProfile')->firstOrFail();
        $entitlements = $school->providerProfile ? app(PartnerPortalService::class)->entitlements($school->providerProfile) : [];
        $course->update([
            'approval_status' => $data['approval_status'],
            'admin_note' => $data['approval_status'] === 'approved' ? null : $data['admin_note'],
            'is_featured' => $data['approval_status'] === 'approved' && $course->is_featured && !empty($entitlements['featured_courses']),
        ]);
        return back()->with(['message' => __('Course review saved.'), 'alert-type' => 'success']);
    }
}
