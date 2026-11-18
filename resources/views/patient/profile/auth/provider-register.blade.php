@extends('layouts.patient.layout')
@section('title')
    <title>{{ __('Provider Registration') }}</title>
@endsection

@section('patient-content')

    <div class="py-5" style="background:#f8f9fa; min-height:calc(100vh - 200px);">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-8 col-xl-7">

                    {{-- Flash messages --}}
                    @include('patient.profile.auth._flash')

                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">

                        {{-- Card header --}}
                        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 px-sm-5">
                            <div class="text-center mb-3">
                                <h4 class="fw-bold text-dark mb-1">{{ __('Join MedFind as a Provider') }}</h4>
                                <p class="text-muted small mb-0">{{ __('Register as a specialist doctor or list your medical facility.') }}</p>
                            </div>

                            {{-- Tab switcher — stays on clinic if clinic errors or ?type=clinic --}}
                            @php
                                $clinicHasErrors = $errors->hasAny(['specialty','phone']) ||
                                    ($errors->has('name') && !$errors->has('licensing_body')) ||
                                    ($errors->has('email') && !$errors->has('licensing_body'));
                                $activeProvider = in_array(request('type'), ['school', 'supplier'], true)
                                    ? request('type')
                                    : ((request('type') === 'clinic' || $clinicHasErrors) ? 'clinic' : 'doctor');
                            @endphp

                            <ul class="nav nav-pills nav-fill p-1 bg-light rounded-pill" id="providerTab" role="tablist">
                                @if($setting->doctor_can_register)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link rounded-pill fw-semibold transition {{ $activeProvider === 'doctor' ? 'active' : '' }}"
                                        id="prov-tab-doctor" data-bs-toggle="pill" data-bs-target="#prov-panel-doctor"
                                        type="button" role="tab" aria-selected="{{ $activeProvider === 'doctor' ? 'true' : 'false' }}">
                                        <i class="fas fa-user-md me-2"></i>{{ __('Specialist ') }}
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation"><button class="nav-link rounded-pill fw-semibold transition {{ $activeProvider === 'school' ? 'active' : '' }}" id="prov-tab-school" data-bs-toggle="pill" data-bs-target="#prov-panel-school" type="button" role="tab" aria-selected="{{ $activeProvider === 'school' ? 'true' : 'false' }}"><i class="fas fa-graduation-cap me-2"></i>{{ __('School') }}</button></li>
                                <li class="nav-item" role="presentation"><button class="nav-link rounded-pill fw-semibold transition {{ $activeProvider === 'supplier' ? 'active' : '' }}" id="prov-tab-supplier" data-bs-toggle="pill" data-bs-target="#prov-panel-supplier" type="button" role="tab" aria-selected="{{ $activeProvider === 'supplier' ? 'true' : 'false' }}"><i class="fas fa-boxes me-2"></i>{{ __('Supplier') }}</button></li>
                                @endif
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link rounded-pill fw-semibold transition {{ $activeProvider === 'clinic' ? 'active' : '' }}"
                                        id="prov-tab-clinic" data-bs-toggle="pill" data-bs-target="#prov-panel-clinic"
                                        type="button" role="tab" aria-selected="{{ $activeProvider === 'clinic' ? 'true' : 'false' }}">
                                        <i class="fas fa-hospital me-2"></i>{{ __('Facility') }}
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <div class="card-body px-4 px-sm-5 pt-4 pb-5">
                            <div class="tab-content" id="providerTabContent">

                                {{-- ────────── DOCTOR PANEL ────────── --}}
                                @if($setting->doctor_can_register)
                                <div class="tab-pane fade {{ $activeProvider === 'doctor' ? 'show active' : '' }}"
                                    id="prov-panel-doctor" role="tabpanel" tabindex="0">

                                    <form action="{{ route('doctor.register') }}" method="POST" enctype="multipart/form-data" novalidate>
                                        @csrf
                                        <div class="row g-3">

                                            <div class="col-12">
                                                <label for="dr_name" class="form-label fw-semibold small text-secondary">{{ __('Full Name') }} <span class="text-danger">*</span></label>
                                                <input name="name" type="text" id="dr_name"
                                                    class="form-control rounded-3 @if($activeProvider === 'doctor' && $errors->has('name')) is-invalid @endif"
                                                    value="{{ old('name') }}" placeholder="Dr. Jane Doe" required>
                                                @if($activeProvider === 'doctor')
                                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                @endif
                                            </div>

                                            <div class="col-12">
                                                <label for="licensing_body_id" class="form-label fw-semibold small text-secondary">{{ __('Licensing Body') }} <span class="text-danger">*</span></label>
                                                <select name="licensing_body_id" id="licensing_body_id"
                                                    class="form-select select2 rounded-3 @error('licensing_body_id') is-invalid @enderror" required>
                                                    <option value="">{{ __('Select Licensing Body') }}</option>
                                                    @foreach($licensingBodies as $body)
                                                        <option value="{{ $body->id }}" @selected(old('licensing_body_id') == $body->id)>{{ $body->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('licensing_body_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>

                                            <div class="col-12">
                                                <label for="insurance_provider_ids" class="form-label fw-semibold small text-secondary">{{ __('Insurance Providers') }}</label>
                                                <select name="insurance_provider_ids[]" id="insurance_provider_ids"
                                                    class="form-select select2 rounded-3 @error('insurance_provider_ids') is-invalid @enderror"
                                                    multiple>
                                                    @foreach($insuranceProviders as $provider)
                                                        <option value="{{ $provider->id }}" @selected(in_array($provider->id, old('insurance_provider_ids', [])))>{{ $provider->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('insurance_provider_ids')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>

                                            <div class="col-sm-6">
                                                <label for="dr_email" class="form-label fw-semibold small text-secondary">{{ __('Professional Email') }} <span class="text-danger">*</span></label>
                                                <input type="email" name="email" id="dr_email"
                                                    class="form-control rounded-3 @if($activeProvider === 'doctor') @error('email') is-invalid @enderror @endif"
                                                    value="{{ old('email') }}" placeholder="doctor@hospital.com" required>
                                                @if($activeProvider === 'doctor')
                                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                @endif
                                            </div>

                                            <div class="col-sm-6">
                                                <label for="dr_phone" class="form-label fw-semibold small text-secondary">{{ __('Phone Number') }} <span class="text-danger">*</span></label>
                                                <input name="phone" type="text" id="dr_phone"
                                                    class="form-control rounded-3 @error('phone') is-invalid @enderror"
                                                    value="{{ old('phone') }}" placeholder="+254 700 000 000" required>
                                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>

                                            @include('patient.profile.auth.partials.photo-field', ['field' => 'image', 'id' => 'dr_image', 'label' => __('Profile photo')])

                                            <div class="col-12">
                                                <label for="department_id" class="form-label fw-semibold small text-secondary">{{ __('Medical Specialty') }} <span class="text-danger">*</span></label>
                                                <select class="form-select select2 rounded-3 @error('department_id') is-invalid @enderror" name="department_id" required>
                                                    <option value="">{{ __('Select Specialty') }}</option>
                                                    @foreach($departments as $dept)
                                                        <option value="{{ $dept->id }}" @selected($dept->id == old('department_id'))>{{ $dept->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>

                                            

                                            <div class="col-sm-6">
                                                <label for="dr_password" class="form-label fw-semibold small text-secondary">{{ __('Password') }} <span class="text-danger">*</span></label>
                                                <input type="password" name="password" id="dr_password"
                                                    class="form-control rounded-3 @error('password') is-invalid @enderror"
                                                    placeholder="••••••••" required>
                                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>

                                            <div class="col-sm-6">
                                                <label for="dr_password_confirmation" class="form-label fw-semibold small text-secondary">{{ __('Confirm Password') }} <span class="text-danger">*</span></label>
                                                <input type="password" name="password_confirmation" id="dr_password_confirmation"
                                                    class="form-control rounded-3" placeholder="••••••••" required>
                                            </div>

                                            @if($setting->recaptcha_status == 'active')
                                                <div class="col-12 d-flex justify-content-center">
                                                    <div class="g-recaptcha" data-sitekey="{{ $setting->recaptcha_site_key }}"></div>
                                                </div>
                                            @endif

                                            <div class="col-12 mt-1">
                                                <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm">
                                                    {{ __('Apply to Join') }}
                                                </button>
                                            </div>
                                        </div>
                                    </form>

                                    <p class="text-center mt-4 mb-0 text-muted small">
                                        {{ __('Already registered?') }}
                                        <a href="{{ route('login', ['type' => 'doctor']) }}" class="text-primary fw-semibold text-decoration-none">{{ __('Sign In') }}</a>
                                    </p>
                                </div>
                                @endif

                                {{-- ────────── CLINIC PANEL ────────── --}}
                                <div class="tab-pane fade {{ $activeProvider === 'clinic' ? 'show active' : '' }}"
                                    id="prov-panel-clinic" role="tabpanel" tabindex="0">

                                    <form action="{{ route('clinic.register') }}" method="POST" enctype="multipart/form-data" novalidate>
                                        @csrf
                                        <div class="row g-3">

                                            <div class="col-12">
                                                <label for="cl_name" class="form-label fw-semibold small text-secondary">{{ __('Clinic / Centre Name') }} <span class="text-danger">*</span></label>
                                                <input name="name" type="text" id="cl_name"
                                                    class="form-control rounded-3 @if($activeProvider === 'clinic' && $errors->has('name') && !$errors->has('licensing_body')) is-invalid @endif"
                                                    value="{{ old('name') }}" placeholder="{{ __('e.g. Nairobi Dialysis Centre') }}" required>
                                                @if($activeProvider === 'clinic' && !$errors->has('licensing_body'))
                                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                @endif
                                            </div>

                                            <div class="col-12">
                                                <label for="cl_specialty" class="form-label fw-semibold small text-secondary">{{ __('Specialty / Type') }} <span class="text-danger">*</span></label>
                                                <input name="specialty" type="text" id="cl_specialty"
                                                    class="form-control rounded-3 @error('specialty') is-invalid @enderror"
                                                    value="{{ old('specialty') }}" placeholder="{{ __('e.g. Dialysis, Dental, Eye Care, Fertility, Imaging') }}" required>
                                                @error('specialty')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>

                                            <div class="col-12">
                                                <label for="clinic_licensing_body_id" class="form-label fw-semibold small text-secondary">{{ __('Licensing Body') }} <span class="text-danger">*</span></label>
                                                <select name="licensing_body_id" id="clinic_licensing_body_id"
                                                    class="form-select select2 rounded-3 @error('licensing_body_id') is-invalid @enderror" required>
                                                    <option value="">{{ __('Select Licensing Body') }}</option>
                                                    @foreach($facilityLicensingBodies as $body)
                                                        <option value="{{ $body->id }}" @selected(old('licensing_body_id') == $body->id)>{{ $body->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('licensing_body_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>

                                            <div class="col-12">
                                                <label for="clinic_insurance_provider_ids" class="form-label fw-semibold small text-secondary">{{ __('Insurance Providers') }}</label>
                                                <select name="insurance_provider_ids[]" id="clinic_insurance_provider_ids"
                                                    class="form-select select2 rounded-3 @error('insurance_provider_ids') is-invalid @enderror"
                                                    multiple>
                                                    @foreach($insuranceProviders as $provider)
                                                        <option value="{{ $provider->id }}" @selected(in_array($provider->id, old('insurance_provider_ids', [])))>{{ $provider->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('insurance_provider_ids')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>

                                            <div class="col-sm-6">
                                                <label for="cl_email" class="form-label fw-semibold small text-secondary">{{ __('Email Address') }} <span class="text-danger">*</span></label>
                                                <input type="email" name="email" id="cl_email"
                                                    class="form-control rounded-3 @if($activeProvider === 'clinic' && !$errors->has('licensing_body')) @error('email') is-invalid @enderror @endif"
                                                    value="{{ old('email') }}" placeholder="clinic@example.com" required>
                                                @if($activeProvider === 'clinic' && !$errors->has('licensing_body'))
                                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                @endif
                                            </div>

                                            <div class="col-sm-6">
                                                <label for="cl_phone" class="form-label fw-semibold small text-secondary">{{ __('Phone Number') }} <span class="text-danger">*</span></label>
                                                <input name="phone" type="text" id="cl_phone"
                                                    class="form-control rounded-3 @error('phone') is-invalid @enderror"
                                                    value="{{ old('phone') }}" placeholder="+254 700 000 000" required>
                                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>

                                            @include('patient.profile.auth.partials.photo-field', ['field' => 'logo', 'id' => 'cl_logo', 'label' => __('Facility logo or photo')])

                                            <div class="col-sm-6">
                                                <label for="cl_password" class="form-label fw-semibold small text-secondary">{{ __('Password') }} <span class="text-danger">*</span></label>
                                                <input type="password" name="password" id="cl_password"
                                                    class="form-control rounded-3 @error('password') is-invalid @enderror"
                                                    placeholder="••••••••" required>
                                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>

                                            <div class="col-sm-6">
                                                <label for="cl_password_confirmation" class="form-label fw-semibold small text-secondary">{{ __('Confirm Password') }} <span class="text-danger">*</span></label>
                                                <input type="password" name="password_confirmation" id="cl_password_confirmation"
                                                    class="form-control rounded-3" placeholder="••••••••" required>
                                            </div>

                                            @if($setting->recaptcha_status == 'active')
                                                <div class="col-12 d-flex justify-content-center">
                                                    <div class="g-recaptcha" data-sitekey="{{ $setting->recaptcha_site_key }}"></div>
                                                </div>
                                            @endif

                                            <div class="col-12 mt-1">
                                                <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm">
                                                    {{ __('List My Facility') }}
                                                </button>
                                            </div>
                                        </div>
                                    </form>

                                    <p class="text-center mt-4 mb-0 text-muted small">
                                        {{ __('Already registered?') }}
                                        <a href="{{ route('login', ['type' => 'clinic']) }}" class="text-primary fw-semibold text-decoration-none">{{ __('Sign In') }}</a>
                                    </p>
                                </div>

                                <div class="tab-pane fade {{ $activeProvider === 'school' ? 'show active' : '' }}" id="prov-panel-school" role="tabpanel" tabindex="0"><div class="text-center mb-3"><h4 class="fw-bold text-dark mb-1">{{ __('Create your Healthcare School account') }}</h4><p class="text-muted small mb-0">{{ __('Complete your profile and choose a package after registration.') }}</p></div><form action="{{ route('website.partner.register.store', ['type' => 'school']) }}" enctype="multipart/form-data" method="POST" class="row g-3">@csrf<div class="col-12"><label class="form-label fw-semibold small text-secondary">{{ __('Institution name') }}</label><input class="form-control rounded-3" name="name" required></div><div class="col-md-6"><label class="form-label fw-semibold small text-secondary">{{ __('Official email') }}</label><input class="form-control rounded-3" type="email" name="email" required></div><div class="col-md-6"><label class="form-label fw-semibold small text-secondary">{{ __('Phone number') }}</label><input class="form-control rounded-3" name="phone" required></div>@include('patient.profile.auth.partials.photo-field', ['field' => 'logo', 'id' => 'school_logo_tab', 'label' => __('Institution logo')])<div class="col-md-6"><label class="form-label fw-semibold small text-secondary">{{ __('Password') }}</label><input class="form-control rounded-3" type="password" name="password" required></div><div class="col-md-6"><label class="form-label fw-semibold small text-secondary">{{ __('Confirm password') }}</label><input class="form-control rounded-3" type="password" name="password_confirmation" required></div><div class="col-12"><button class="btn btn-primary w-100 rounded-pill py-2 fw-bold">{{ __('Continue to packages') }}</button></div></form></div>

                                <div class="tab-pane fade {{ $activeProvider === 'supplier' ? 'show active' : '' }}" id="prov-panel-supplier" role="tabpanel" tabindex="0"><div class="text-center mb-3"><h4 class="fw-bold text-dark mb-1">{{ __('Create your Medical Supplier account') }}</h4><p class="text-muted small mb-0">{{ __('Complete your profile and choose a package after registration.') }}</p></div><form action="{{ route('website.partner.register.store', ['type' => 'supplier']) }}" enctype="multipart/form-data" method="POST" class="row g-3">@csrf<div class="col-12"><label class="form-label fw-semibold small text-secondary">{{ __('Company name') }}</label><input class="form-control rounded-3" name="name" required></div><div class="col-md-6"><label class="form-label fw-semibold small text-secondary">{{ __('Official email') }}</label><input class="form-control rounded-3" type="email" name="email" required></div><div class="col-md-6"><label class="form-label fw-semibold small text-secondary">{{ __('Phone number') }}</label><input class="form-control rounded-3" name="phone" required></div>@include('patient.profile.auth.partials.photo-field', ['field' => 'logo', 'id' => 'supplier_logo_tab', 'label' => __('Company logo')])<div class="col-md-6"><label class="form-label fw-semibold small text-secondary">{{ __('Password') }}</label><input class="form-control rounded-3" type="password" name="password" required></div><div class="col-md-6"><label class="form-label fw-semibold small text-secondary">{{ __('Confirm password') }}</label><input class="form-control rounded-3" type="password" name="password_confirmation" required></div><div class="col-12"><button class="btn btn-primary w-100 rounded-pill py-2 fw-bold">{{ __('Continue to packages') }}</button></div></form></div>

                            </div>{{-- /tab-content --}}
                        </div>{{-- /card-body --}}
                    </div>{{-- /card --}}

                </div>
            </div>
        </div>
    </div>

@endsection

@push('js')
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<style>
    .transition { transition: all 0.2s ease-in-out; }
    .nav-pills .nav-link { color: #6c757d; padding: 0.5rem 1rem; }
    .nav-pills .nav-link.active { background:#fff !important; color:#0d6efd !important; box-shadow:0 2px 8px rgba(0,0,0,0.07); }
    .form-control:focus, .form-select:focus { border-color:#0d6efd; box-shadow:0 0 0 0.2rem rgba(13,110,253,.15); }
    .select2-container--default .select2-selection--single { height:42px !important; padding:6px 12px; border-radius:8px !important; border:1px solid #dee2e6 !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height:40px !important; }
    @media(max-width:420px) { .g-recaptcha { transform:scale(0.77); transform-origin:0 0; } }
</style>
@endpush
