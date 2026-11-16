<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\HealthcareInstitution\app\Models\HealthcareCourse;
use Modules\HealthcareInstitution\app\Models\HealthcareInstitution;
use Modules\HealthcareInstitution\app\Models\HealthcareInstitutionEnquiry;
use App\Services\PartnerPortalService;

class SchoolDirectoryController extends Controller
{
    public function index(Request $request): View
    {
        $term = trim((string) $request->query('q', ''));
        $portal = app(PartnerPortalService::class);
        $institutions = HealthcareInstitution::published()
            ->whereHas('providerProfile', fn ($q) => $q->whereHas('subscriptions', fn ($s) => $s->where('status', \App\Models\ProviderSubscription::STATUS_APPROVED)->where(fn ($w) => $w->whereNull('starts_at')->orWhere('starts_at', '<=', now()))->where(fn ($w) => $w->whereNull('expires_at')->orWhere('expires_at', '>', now()))))
            ->when($term !== '', fn ($q) => $q->where(fn ($s) => $s->where('name', 'like', "%{$term}%")
                ->orWhere('location', 'like', "%{$term}%")
                ->orWhereHas('courses', fn ($c) => $c->where('approval_status', 'approved')->where('name', 'like', "%{$term}%"))))
            ->with(['providerProfile.subscriptions', 'courses' => fn ($q) => $q->where('approval_status', 'approved')->orderBy('name')])
            ->orderBy('name')->paginate(12)->withQueryString();
        $publishedCourses = HealthcareCourse::query()->where('approval_status', 'approved')->whereHas('institution', fn ($q) => $q->published())
            ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
            ->select('name', 'slug')->distinct()->orderBy('name')->limit(100)->get();
        $plans = app(\Modules\Doctor\app\Services\SubscriptionPlanService::class)->for('school');
        $institutions->getCollection()->transform(function ($institution) use ($portal) {
            $institution->public_entitlements = $institution->providerProfile ? $portal->entitlements($institution->providerProfile) : [];
            $institution->public_courses = $institution->courses->filter(fn ($course) => ! $course->is_featured || ! empty($institution->public_entitlements['featured_courses']))->take(6);
            if (empty($institution->public_entitlements['campus_facilities'])) $institution->facilities = null;
            if (empty($institution->public_entitlements['practical_training'])) $institution->practical_training = null;
            if (empty($institution->public_entitlements['accommodation'])) $institution->accommodation = null;
            if (empty($institution->public_entitlements['international_pathways'])) $institution->international_pathways = null;
            if (empty($institution->public_entitlements['intake_promotion'])) { $institution->intake_campaign_title = null; $institution->intake_campaign_ends_at = null; }
            return $institution;
        });
        return view('patient.healthcare-courses', compact('institutions', 'publishedCourses', 'term', 'plans'));
    }

    public function course(string $slug): View
    {
        $courseName = str_replace('-', ' ', $slug);
        $institutions = HealthcareInstitution::published()
            ->whereHas('courses', fn ($q) => $q->where('approval_status', 'approved')->where('slug', $slug))
            ->with(['providerProfile.subscriptions', 'courses' => fn ($q) => $q->where('approval_status', 'approved')->where('slug', $slug)])
            ->orderBy('name')->paginate(12);
        abort_if($institutions->isEmpty(), 404);
        $title = $institutions->first()->courses->first()?->name ?? Str::headline($courseName);
        return view('patient.healthcare-course-show', compact('slug', 'title', 'institutions'));
    }

    public function institution(string $slug): View
    {
        $institution = HealthcareInstitution::published()
            ->where('slug', $slug)
            ->with(['providerProfile.subscriptions', 'courses' => fn ($q) => $q->where('approval_status', 'approved')->orderBy('name')])
            ->firstOrFail();
        $institution->public_entitlements = $institution->providerProfile ? app(PartnerPortalService::class)->entitlements($institution->providerProfile) : [];
        $institution->public_courses = $institution->courses->filter(fn ($course) => ! $course->is_featured || ! empty($institution->public_entitlements['featured_courses']));
        $institution->public_courses = $institution->public_courses->map(function ($course) use ($institution) {
            if (empty($institution->public_entitlements['application_information'])) $course->application_information = null;
            return $course;
        });
        if (empty($institution->public_entitlements['campus_facilities'])) $institution->facilities = null;
        if (empty($institution->public_entitlements['practical_training'])) $institution->practical_training = null;
        if (empty($institution->public_entitlements['accommodation'])) $institution->accommodation = null;
        if (empty($institution->public_entitlements['international_pathways'])) $institution->international_pathways = null;
        if (empty($institution->public_entitlements['intake_promotion'])) { $institution->intake_campaign_title = null; $institution->intake_campaign_ends_at = null; }
        return view('patient.healthcare-institution-show', compact('institution'));
    }

    public function enquire(Request $request, string $slug): RedirectResponse
    {
        $institution = HealthcareInstitution::published()->where('slug', $slug)->firstOrFail();
        $data = $request->validate([
            'healthcare_course_id' => ['nullable', 'integer', 'exists:healthcare_courses,id'],
            'name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'], 'organisation' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:3000'],
        ]);
        if (! empty($data['healthcare_course_id'])) {
            abort_unless($institution->courses()->where('id', $data['healthcare_course_id'])->where('approval_status', 'approved')->exists(), 422);
        }
        $enquiry = $institution->enquiries()->create($data);
        try { Mail::raw($data['message'], fn ($mail) => $mail->to($institution->official_email)->replyTo($data['email'], $data['name'])->subject(__('New healthcare course enquiry'))); }
        catch (\Throwable $exception) { report($exception); }
        return back()->with('message', __('Your enquiry has been sent to the institution.'))->with('enquiry_id', $enquiry->id);
    }
}
