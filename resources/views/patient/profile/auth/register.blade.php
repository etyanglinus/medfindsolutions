@extends('layouts.patient.layout')

@section('title')
    <title>{{ __('Create Account') }} — {{ $setting->app_name }}</title>
@endsection

@section('patient-content')

    @include('patient.profile.auth._hero', ['title' => __('Create Account'), 'breadcrumb' => __('Sign Up')])

    <div class="py-5" style="background:#f8f9fa; min-height:calc(100vh - 200px);">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">

                    @include('patient.profile.auth._flash')

                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">

                        <div class="card-header bg-white border-0 pt-4 pb-3 px-4 px-sm-5 text-center">
                            <h5 class="fw-bold text-dark mb-1">{{ __('Join MedFind') }}</h5>
                            <p class="text-muted small mb-0">{{ __('Find and connect with the best specialists near you.') }}</p>
                        </div>

                        <div class="card-body px-4 px-sm-5 pt-2 pb-5">

                            {{-- Google OAuth removed --}}

                            <form action="{{ route('patient-register') }}" method="POST" novalidate>
                                @csrf
                                <div class="row g-3">

                                    <div class="col-12">
                                        <label for="r_name" class="form-label fw-semibold small text-secondary">
                                            {{ __('Full Name') }} <span class="text-danger">*</span>
                                        </label>
                                        <input name="name" type="text" id="r_name"
                                            class="form-control rounded-3 @error('name') is-invalid @enderror"
                                            value="{{ old('name') }}"
                                            placeholder="Jane Doe"
                                            autocomplete="name" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label for="r_email" class="form-label fw-semibold small text-secondary">
                                            {{ __('Email Address') }} <span class="text-danger">*</span>
                                        </label>
                                        <input type="email" name="email" id="r_email"
                                            class="form-control rounded-3 @error('email') is-invalid @enderror"
                                            value="{{ old('email') }}"
                                            placeholder="jane@example.com"
                                            autocomplete="email" required>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label for="r_phone" class="form-label fw-semibold small text-secondary">
                                            {{ __('Phone Number') }} <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="phone" id="r_phone"
                                            class="form-control rounded-3 @error('phone') is-invalid @enderror"
                                            value="{{ old('phone') }}"
                                            placeholder="1234567890"
                                            pattern="[0-9]*" inputmode="numeric"
                                            oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                                            autocomplete="tel" required>
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label for="r_password" class="form-label fw-semibold small text-secondary">
                                            {{ __('Password') }} <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="password" name="password" id="r_password"
                                                class="form-control rounded-start-3 @error('password') is-invalid @enderror"
                                                placeholder="••••••••"
                                                pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}"
                                                autocomplete="new-password" required>
                                            <button type="button"
                                                class="btn btn-outline-secondary rounded-end-3 toggle-pw"
                                                data-target="r_password" tabindex="-1">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="form-text text-muted" style="font-size:.78rem;">
                                            {{ __('Must be at least 8 characters long and include uppercase, lowercase, numbers, and symbols.') }}
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label for="r_password_confirmation" class="form-label fw-semibold small text-secondary">
                                            {{ __('Confirm Password') }} <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="password" name="password_confirmation" id="r_password_confirmation"
                                                class="form-control rounded-start-3"
                                                placeholder="••••••••"
                                                autocomplete="new-password" required>
                                            <button type="button"
                                                class="btn btn-outline-secondary rounded-end-3 toggle-pw"
                                                data-target="r_password_confirmation" tabindex="-1">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    @if($setting->recaptcha_status == 'active')
                                        <div class="col-12 d-flex justify-content-center recaptcha-wrap">
                                            <div class="g-recaptcha" data-sitekey="{{ $setting->recaptcha_site_key }}"></div>
                                        </div>
                                    @endif

                                    <div class="col-12 mt-1">
                                        <button type="submit"
                                            class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm">
                                            {{ __('Create Account') }}
                                        </button>
                                    </div>

                                </div>
                            </form>

                            <p class="text-center mt-4 mb-0 text-muted small">
                                {{ __('Already have an account?') }}
                                <a href="{{ route('login') }}" class="text-primary fw-semibold text-decoration-none ms-1">{{ __('Sign In') }}</a>
                            </p>

                        </div>{{-- /card-body --}}
                    </div>{{-- /card --}}

                </div>
            </div>
        </div>
    </div>

@endsection

@push('js')
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
"use strict";
document.querySelectorAll('.toggle-pw').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var inp = document.getElementById(this.dataset.target);
        var icon = this.querySelector('i');
        if (inp.type === 'password') {
            inp.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            inp.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
});
</script>
<style>
    .form-control:focus { border-color:#0d6efd; box-shadow:0 0 0 .2rem rgba(13,110,253,.15); }
    @media(max-width:380px) { .recaptcha-wrap { overflow-x:auto; } .g-recaptcha { transform:scale(.8); transform-origin:0 0; } }
</style>
@endpush