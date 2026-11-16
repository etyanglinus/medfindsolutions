<?php

namespace App\Http\Middleware;

use App\Services\ProviderOnboardingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class RequireProviderOnboarding
{
    public function handle(Request $request, Closure $next)
    {
        $name = $request->route()?->getName() ?? '';
        $apiDoctor = str_starts_with($request->route()?->uri() ?? '', 'api/doctor/');
        $type = str_starts_with($name, 'doctor.') ? 'doctor' : (str_starts_with($name, 'clinic.') ? 'clinic' : (str_starts_with($name, 'website.partner.') ? $request->route('type') : null));
        if ($apiDoctor) $type = 'doctor';
        if (in_array($name, ['listing.report', 'listing.inbox', 'listing.inbox.update', 'listing.inquiries.update'], true)) $type = $request->route('type');
        if (!in_array($type, ['doctor', 'clinic', 'school', 'supplier'])) return $next($request);
        $client = auth($apiDoctor ? 'doctor_api' : $type)->user();
        if ($apiDoctor && !($client instanceof \Modules\Doctor\app\Models\Doctor)) return $next($request);
        if (!$client) return $next($request);
        $status = app(ProviderOnboardingService::class)->status($client, $type);
        View::share('providerOnboarding', $status);
        if ($status['complete']) return $next($request);
        $allowed = ['doctor.edit-profile', 'doctor.profile-update', 'doctor.documents.index', 'doctor.documents.store', 'doctor.change-password', 'doctor.update-password', 'doctor.logout', 'doctor.verification.resend',
            'clinic.edit-profile', 'clinic.profile.update', 'clinic.documents.index', 'clinic.documents.store', 'clinic.change-password', 'clinic.password.update', 'clinic.logout', 'clinic.verification.resend',
            'website.partner.profile', 'website.partner.profile.update', 'website.partner.documents.index', 'website.partner.documents.store', 'website.partner.account', 'website.partner.account.update', 'website.partner.logout'];
        if (in_array($name, $allowed) || str_contains($name, 'subscription.') || str_contains($name, 'verification.') || str_contains($name, 'registration.')) return $next($request);
        if ($apiDoctor && in_array($request->route()->uri(), ['api/doctor/profile', 'api/doctor/profile-update', 'api/doctor/password-update', 'api/doctor/logout', 'api/doctor/logout/all-app'])) return $next($request);
        if ($request->expectsJson()) return response()->json(['message' => __('Complete your profile, upload a profile photo and submit verification documents before using your dashboard.'), 'onboarding' => $status], 403);
        return redirect()->to($status['next_url'])->with(['message' => __('Complete your profile, upload a profile photo and submit verification documents to continue.'), 'alert-type' => 'warning']);
    }
}
