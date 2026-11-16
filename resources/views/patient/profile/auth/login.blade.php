@extends('layouts.patient.layout')

@section('title')
    <title>{{ __('Sign In') }} — {{ $setting->app_name }}</title>
@endsection

@section('patient-content')

    <div class="mf-auth-page py-5 bg-light min-vh-100">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">

                    @include('patient.profile.auth._flash')

                    {{-- Active tab resolution (Default: specialist) --}}
                    @php
                        $loginTab = match(request('type')) {
                            'clinic' => 'clinic',
                            'school' => 'school',
                            'supplier' => 'supplier',
                            default  => 'specialist',
                        };
                    @endphp

                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">

                        {{-- Card Header --}}
                        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 px-sm-5">
                            <div class="text-center mb-3">
                                <h5 class="fw-bold text-dark mb-0">{{ __('Welcome back') }}</h5>
                                <p class="text-muted small mt-1 mb-0">{{ __('Sign in to your account') }}</p>
                            </div>
                            <ul class="nav nav-pills nav-fill p-1 bg-light rounded-pill" id="loginTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button type="button" role="tab"
                                        class="nav-link rounded-pill fw-semibold small {{ $loginTab === 'specialist' ? 'active' : '' }}"
                                        data-bs-toggle="pill" data-bs-target="#login-specialist"
                                        aria-selected="{{ $loginTab === 'specialist' ? 'true' : 'false' }}">
                                        <i class="fas fa-user-md me-1"></i>{{ __('Specialist') }}
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button type="button" role="tab" class="nav-link rounded-pill fw-semibold small {{ $loginTab === 'school' ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#login-school" aria-selected="{{ $loginTab === 'school' ? 'true' : 'false' }}"><i class="fas fa-graduation-cap me-1"></i>{{ __('School') }}</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button type="button" role="tab" class="nav-link rounded-pill fw-semibold small {{ $loginTab === 'supplier' ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#login-supplier" aria-selected="{{ $loginTab === 'supplier' ? 'true' : 'false' }}"><i class="fas fa-boxes me-1"></i>{{ __('Supplier') }}</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button type="button" role="tab"
                                        class="nav-link rounded-pill fw-semibold small {{ $loginTab === 'clinic' ? 'active' : '' }}"
                                        data-bs-toggle="pill" data-bs-target="#login-clinic"
                                        aria-selected="{{ $loginTab === 'clinic' ? 'true' : 'false' }}">
                                        <i class="fas fa-hospital me-1"></i>{{ __('Clinic') }}
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <div class="card-body px-4 px-sm-5 pt-4 pb-5">
                            <div class="tab-content" id="loginTabContent">

                                {{-- SPECIALIST TAB --}}
                                <div class="tab-pane fade {{ $loginTab === 'specialist' ? 'show active' : '' }}"
                                    id="login-specialist" role="tabpanel" tabindex="0">

                                    <form action="{{ route('doctor.login') }}" method="POST" novalidate>
                                        @csrf
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label for="s_email" class="form-label fw-semibold small text-secondary">{{ __('Email Address') }}</label>
                                                <input type="email" name="email" id="s_email"
                                                    class="form-control rounded-3 @error('email') is-invalid @enderror"
                                                    value="{{ old('email') }}"
                                                    placeholder="specialist@hospital.com"
                                                    autocomplete="email" required>
                                                @error('email')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-12">
                                                <label for="s_password" class="form-label fw-semibold small text-secondary">{{ __('Password') }}</label>
                                                <div class="input-group">
                                                    <input type="password" name="password" id="s_password"
                                                        class="form-control rounded-start-3"
                                                        placeholder="••••••••"
                                                        autocomplete="current-password" required>
                                                    <button type="button" class="btn btn-outline-secondary rounded-end-3 toggle-pw" data-target="s_password" tabindex="-1">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            @if($setting->recaptcha_status == 'active')
                                                <div class="col-12 d-flex justify-content-center recaptcha-wrap">
                                                    <div class="g-recaptcha" data-sitekey="{{ $setting->recaptcha_site_key }}"></div>
                                                </div>
                                            @endif

                                            <div class="col-12">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <div class="form-check mb-0">
                                                        <input class="form-check-input" type="checkbox" name="doctor_remember" id="s_remember" {{ old('doctor_remember') ? 'checked' : '' }}>
                                                        <label class="form-check-label small text-muted" for="s_remember">{{ __('Remember me') }}</label>
                                                    </div>
                                                    <a href="{{ route('password.request', ['type' => 'specialist']) }}" class="small text-primary fw-semibold text-decoration-none">{{ __('Forgot password?') }}</a>
                                                </div>
                                                <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm">
                                                    {{ __('Sign In') }}
                                                </button>
                                            </div>
                                        </div>
                                    </form>

                                    @if($setting->doctor_can_register)
                                        <p class="text-center mt-4 mb-0 text-muted small">
                                            {{ __('New Specialist?') }}
                                            <a href="{{ route('provider.register', ['type' => 'specialist']) }}" class="text-primary fw-semibold text-decoration-none ms-1">{{ __('Apply to join') }}</a>
                                        </p>
                                    @endif
                                </div>

                                {{-- CLINIC TAB --}}
                                <div class="tab-pane fade {{ $loginTab === 'clinic' ? 'show active' : '' }}"
                                    id="login-clinic" role="tabpanel" tabindex="0">

                                    <form action="{{ route('clinic.login') }}" method="POST" novalidate>
                                        @csrf
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label for="cl_email" class="form-label fw-semibold small text-secondary">{{ __('Clinic Email') }}</label>
                                                <input type="email" name="email" id="cl_email"
                                                    class="form-control rounded-3 @error('email') is-invalid @enderror"
                                                    value="{{ old('email') }}"
                                                    placeholder="clinic@example.com"
                                                    autocomplete="email" required>
                                                @error('email')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-12">
                                                <label for="cl_password" class="form-label fw-semibold small text-secondary">{{ __('Password') }}</label>
                                                <div class="input-group">
                                                    <input type="password" name="password" id="cl_password"
                                                        class="form-control rounded-start-3"
                                                        placeholder="••••••••"
                                                        autocomplete="current-password" required>
                                                    <button type="button" class="btn btn-outline-secondary rounded-end-3 toggle-pw" data-target="cl_password" tabindex="-1">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            @if($setting->recaptcha_status == 'active')
                                                <div class="col-12 d-flex justify-content-center recaptcha-wrap">
                                                    <div class="g-recaptcha" data-sitekey="{{ $setting->recaptcha_site_key }}"></div>
                                                </div>
                                            @endif

                                            <div class="col-12">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <div class="form-check mb-0">
                                                        <input class="form-check-input" type="checkbox" name="remember" id="cl_remember" {{ old('remember') ? 'checked' : '' }}>
                                                        <label class="form-check-label small text-muted" for="cl_remember">{{ __('Remember me') }}</label>
                                                    </div>
                                                    <a href="{{ route('password.request', ['type' => 'clinic']) }}" class="small text-primary fw-semibold text-decoration-none">{{ __('Forgot password?') }}</a>
                                                </div>
                                                <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm">
                                                    {{ __('Sign In') }}
                                                </button>
                                            </div>
                                        </div>
                                    </form>

                                    <p class="text-center mt-4 mb-0 text-muted small">
                                        {{ __('Not listed yet?') }}
                                        <a href="{{ route('provider.register', ['type' => 'clinic']) }}" class="text-primary fw-semibold text-decoration-none ms-1">{{ __('List your facility') }}</a>
                                    </p>
                                </div>

                                <div class="tab-pane fade {{ $loginTab === 'school' ? 'show active' : '' }}" id="login-school" role="tabpanel" tabindex="0"><form action="{{ route('website.partner.login.store', ['type' => 'school']) }}" method="POST" class="row g-3">@csrf<div class="col-12"><label class="form-label fw-semibold small text-secondary">{{ __('Official email') }}</label><input class="form-control rounded-3" type="email" name="email" required></div><div class="col-12"><label class="form-label fw-semibold small text-secondary">{{ __('Password') }}</label><input class="form-control rounded-3" type="password" name="password" required></div><div class="col-12"><button class="btn btn-primary w-100 rounded-pill py-2 fw-bold">{{ __('Sign in as School') }}</button></div></form><p class="text-center mt-4 mb-0 text-muted small">{{ __('New school?') }} <a href="{{ route('provider.register', ['type' => 'school']) }}" class="text-primary fw-semibold">{{ __('Create account') }}</a></p></div>

                                <div class="tab-pane fade {{ $loginTab === 'supplier' ? 'show active' : '' }}" id="login-supplier" role="tabpanel" tabindex="0"><form action="{{ route('website.partner.login.store', ['type' => 'supplier']) }}" method="POST" class="row g-3">@csrf<div class="col-12"><label class="form-label fw-semibold small text-secondary">{{ __('Official email') }}</label><input class="form-control rounded-3" type="email" name="email" required></div><div class="col-12"><label class="form-label fw-semibold small text-secondary">{{ __('Password') }}</label><input class="form-control rounded-3" type="password" name="password" required></div><div class="col-12"><button class="btn btn-primary w-100 rounded-pill py-2 fw-bold">{{ __('Sign in as Supplier') }}</button></div></form><p class="text-center mt-4 mb-0 text-muted small">{{ __('New supplier?') }} <a href="{{ route('provider.register', ['type' => 'supplier']) }}" class="text-primary fw-semibold">{{ __('Create account') }}</a></p></div>

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

@endsection

@push('js')
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // Toggle Password Visibility
    document.querySelectorAll('.toggle-pw').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const input = document.getElementById(this.dataset.target);
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    });

    // Handle reCAPTCHA rendering on Bootstrap tab changes
    const tabEls = document.querySelectorAll('button[data-bs-toggle="pill"]');
    tabEls.forEach(function(tabEl) {
        tabEl.addEventListener('shown.bs.tab', function (e) {
            if (typeof grecaptcha !== 'undefined') {
                const targetPane = document.querySelector(e.target.getAttribute('data-bs-target'));
                const recaptcha = targetPane.querySelector('.g-recaptcha');
                if (recaptcha && !recaptcha.hasChildNodes()) {
                    grecaptcha.render(recaptcha, {
                        'sitekey': '{{ $setting->recaptcha_site_key }}'
                    });
                }
            }
        });
    });
});
</script>
<style>
    .fs-7 { font-size: 0.875rem; }
    .nav-pills .nav-link { color: #6c757d; padding: .45rem .75rem; font-size: .85rem; }
    .nav-pills .nav-link.active { background: #fff !important; color: #0d6efd !important; box-shadow: 0 2px 8px rgba(0,0,0,.07); }
    .form-control:focus { border-color: #0d6efd; box-shadow: 0 0 0 .2rem rgba(13,110,253,.15); }
    @media(max-width: 380px) { 
        .recaptcha-wrap { overflow-x: auto; } 
        .g-recaptcha { transform: scale(.8); transform-origin: 0 0; } 
    }
</style>
@endpush