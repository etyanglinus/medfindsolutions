{{--
    Provider email-verification banner.
    Shown inside doctor and clinic portal layouts when email is unverified.

    Usage:
      @include('layouts.provider.verify-banner', ['guard' => 'doctor'])
      @include('layouts.provider.verify-banner', ['guard' => 'clinic'])
--}}

@php
    $guard        = $guard ?? 'doctor';
    $provider     = Auth::guard($guard)->user();
    $resendRoute  = $guard === 'clinic' ? 'clinic.verification.resend' : 'doctor.verification.resend';
    $formId       = 'resend-verify-' . $guard;
    $bannerId     = 'verify-banner-' . $guard;
    $sessionKey   = 'verifyBannerDismissed_' . $guard;
@endphp

@if($provider && ! $provider->email_verified_at)
<div id="{{ $bannerId }}"
     style="background:#fff3cd; border-bottom:2px solid #ffc107; position:sticky; top:0; z-index:1050;"
     role="alert">
    <div class="px-3 py-2">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">

            {{-- Message --}}
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-warning text-dark fw-bold" style="font-size:.72rem; letter-spacing:.4px; white-space:nowrap;">
                    <i class="fas fa-envelope-open me-1"></i>{{ __('EMAIL UNVERIFIED') }}
                </span>
                <span class="text-dark small fw-medium">
                    {{ __('Verify your email to activate all features.') }}
                    <span class="text-muted" style="font-size:.78rem;">({{ $provider->email }})</span>
                </span>
            </div>

            {{-- Actions --}}
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                {{-- Hidden form so the button can be outside the form tag --}}
                <form id="{{ $formId }}" method="POST" action="{{ route($resendRoute) }}" class="d-none">
                    @csrf
                </form>

                <button type="submit" form="{{ $formId }}"
                    class="btn btn-warning btn-sm rounded-pill fw-semibold px-3"
                    style="font-size:.78rem; white-space:nowrap;">
                    <i class="fas fa-paper-plane me-1"></i>{{ __('Send Verification Email') }}
                </button>

                <button type="button"
                    class="btn btn-sm btn-outline-secondary rounded-pill px-2 dismiss-verify-banner"
                    data-banner="{{ $bannerId }}"
                    data-key="{{ $sessionKey }}"
                    aria-label="{{ __('Dismiss') }}"
                    style="font-size:.75rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

        </div>
    </div>
</div>

@once
{{-- Only emit the script once even if included multiple times --}}
<script>
"use strict";
(function () {
    function hideBanner(bannerId, storageKey) {
        var el = document.getElementById(bannerId);
        if (!el) return;
        sessionStorage.setItem(storageKey, '1');
        el.style.transition = 'opacity .25s';
        el.style.opacity    = '0';
        setTimeout(function () { el.style.display = 'none'; }, 270);
    }

    // Restore dismiss state on page load
    document.querySelectorAll('[id^="verify-banner-"]').forEach(function (el) {
        var key = 'verifyBannerDismissed_' + el.id.replace('verify-banner-', '');
        if (sessionStorage.getItem(key)) el.style.display = 'none';
    });

    // Wire dismiss buttons
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.dismiss-verify-banner');
        if (!btn) return;
        hideBanner(btn.dataset.banner, btn.dataset.key);
    });
})();
</script>
@endonce
@endif
