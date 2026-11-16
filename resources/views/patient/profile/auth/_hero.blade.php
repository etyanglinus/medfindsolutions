{{--
    Shared auth hero banner.
    Usage: @include('patient.profile.auth._hero', ['title' => __('Sign In'), 'breadcrumb' => __('Sign In')])
--}}
<div class="auth-hero d-flex align-items-center text-white"
    style="min-height:200px; background:linear-gradient(135deg,#0d6efd 0%,#0a58ca 100%);">
    <div class="container py-5">
        <h1 class="fw-bold mb-1" style="font-size:2rem;">{{ $title }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0" style="background:transparent; padding:0;">
                <li class="breadcrumb-item">
                    <a href="{{ url('/') }}" class="text-white text-opacity-75 text-decoration-none">{{ __('Home') }}</a>
                </li>
                <li class="breadcrumb-item active text-white">{{ $breadcrumb ?? $title }}</li>
            </ol>
        </nav>
    </div>
</div>
