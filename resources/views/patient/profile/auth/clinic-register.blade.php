@extends('layouts.patient.layout')
@section('title')
    <title>{{ __('Register Your Clinic') }}</title>
@endsection

@section('patient-content')
    <div class="banner-area flex"
        style="background-image:url({{ $setting?->breadcrumb_image ? url($setting?->breadcrumb_image) : '' }});">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="banner-text">
                        <h1>{{ __('Clinic Registration') }}</h1>
                        <ul>
                            <li><a href="{{ url('/') }}">{{ __('Home') }}</a></li>
                            <li><span>{{ __('Clinic Registration') }}</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="register-area pt_70 pb_70">
        <div class="container wow fadeIn">
            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="login_area_bg">
                        <div class="section-title text-center mb-4">
                            <h2>{{ __('List Your Clinic on MedFind') }}</h2>
                            <p>{{ __('Create your clinic account. After registration you will choose a subscription package.') }}</p>
                        </div>

                        <div class="regiser-form login-form">
                            <form action="{{ route('clinic.register') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="name">{{ __('Clinic / Centre Name') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="name" id="name"
                                            class="form-control @error('name') is-invalid @enderror"
                                            placeholder="{{ __('e.g. Nairobi Dialysis Centre') }}"
                                            value="{{ old('name') }}">
                                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-12">
                                        <label for="specialty">{{ __('Specialty / Type') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="specialty" id="specialty"
                                            class="form-control @error('specialty') is-invalid @enderror"
                                            placeholder="{{ __('e.g. Dialysis, Dental, Eye Care, Fertility, Imaging') }}"
                                            value="{{ old('specialty') }}">
                                        @error('specialty')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="email">{{ __('Email Address') }} <span class="text-danger">*</span></label>
                                        <input type="email" name="email" id="email"
                                            class="form-control @error('email') is-invalid @enderror"
                                            placeholder="clinic@example.com"
                                            value="{{ old('email') }}">
                                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="phone">{{ __('Phone Number') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="phone" id="phone"
                                            class="form-control @error('phone') is-invalid @enderror"
                                            placeholder="+254 700 000 000"
                                            value="{{ old('phone') }}">
                                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    @include('patient.profile.auth.partials.photo-field', ['field' => 'logo', 'id' => 'clinic_logo', 'label' => __('Facility logo or photo')])

                                    <div class="col-md-6">
                                        <label for="password">{{ __('Password') }} <span class="text-danger">*</span></label>
                                        <input type="password" name="password" id="password"
                                            class="form-control @error('password') is-invalid @enderror"
                                            placeholder="••••••••">
                                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="password_confirmation">{{ __('Confirm Password') }} <span class="text-danger">*</span></label>
                                        <input type="password" name="password_confirmation" id="password_confirmation"
                                            class="form-control"
                                            placeholder="••••••••">
                                    </div>

                                    <div class="col-12 mt-2">
                                        <button type="submit" class="btn btn-primary px-5">
                                            {{ __('Continue to Select Package') }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="mt-4 text-center">
                            <p>{{ __('Already registered?') }}
                                <a href="{{ route('login') }}">{{ __('Login') }}</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
