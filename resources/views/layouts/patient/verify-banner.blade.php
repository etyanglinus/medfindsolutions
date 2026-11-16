{{--
    Email verification banner — shown to authenticated patients who haven't verified yet.
    Sits between the navbar and the page content.
--}}
@auth('web')
    @if(! auth('web')->user()->email_verified_at)
        <div id="verify-email-banner"
             style="background:#fff3cd; border-bottom:2px solid #ffc107; position:sticky; top:0; z-index:1025;"
             role="alert">
            <div class="container py-2">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">

                    {{-- Message --}}
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark fw-bold" style="font-size:.75rem; letter-spacing:.4px;">
                            <i class="fas fa-envelope me-1"></i>{{ __('UNVERIFIED') }}
                        </span>
                        <span class="text-dark small fw-medium">
                            {{ __("Please verify your email address to unlock all features.") }}
                            <span class="text-muted" style="font-size:.8rem;">
                                ({{ auth('web')->user()->email }})
                            </span>
                        </span>
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        {{-- Resend button — submits a hidden form --}}
                        <form method="POST" action="{{ route('verification.resend') }}" id="resend-verify-form" class="m-0">
                            @csrf
                        </form>
                        <button type="submit" form="resend-verify-form"
                            class="btn btn-warning btn-sm rounded-pill fw-semibold px-3"
                            style="font-size:.8rem;">
                            <i class="fas fa-paper-plane me-1"></i>{{ __('Verify Now') }}
                        </button>

                        {{-- Dismiss button — hides banner until next page load --}}
                        <button type="button" id="dismiss-verify-banner"
                            class="btn btn-sm btn-outline-secondary rounded-pill px-2"
                            style="font-size:.75rem;" aria-label="{{ __('Dismiss') }}">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <script>
        "use strict";
        (function () {
            // Hide the banner if already dismissed this session
            if (sessionStorage.getItem('verifyBannerDismissed')) {
                var el = document.getElementById('verify-email-banner');
                if (el) el.style.display = 'none';
            }

            // Dismiss button
            var btn = document.getElementById('dismiss-verify-banner');
            if (btn) {
                btn.addEventListener('click', function () {
                    sessionStorage.setItem('verifyBannerDismissed', '1');
                    var el = document.getElementById('verify-email-banner');
                    if (el) {
                        el.style.transition = 'opacity .25s ease';
                        el.style.opacity = '0';
                        setTimeout(function () { el.style.display = 'none'; }, 260);
                    }
                });
            }
        })();
        </script>
    @endif
@endauth
