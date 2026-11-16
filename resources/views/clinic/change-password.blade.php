@extends('clinic.master_layout')
@section('title')
    <title>{{ __('Change Password') }}</title>
@endsection

@section('clinic-content')
<div class="main-content">
    <section class="section">
        <x-admin.breadcrumb title="{{ __('Change Password') }}" :list="[
            __('Dashboard') => route('clinic.dashboard'),
            __('Change Password') => '#',
        ]" />

        <div class="section-body">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><h4>{{ __('Change Password') }}</h4></div>
                        <div class="card-body">
                            <form action="{{ route('clinic.password.update') }}" method="POST">
                                @csrf @method('PUT')
                                <div class="row g-3">

                                    <div class="col-12">
                                        <label class="form-label fw-bold">{{ __('Current Password') }} <span class="text-danger">*</span></label>
                                        <input type="password" name="current_password"
                                            class="form-control @error('current_password') is-invalid @enderror" required>
                                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-bold">{{ __('New Password') }} <span class="text-danger">*</span></label>
                                        <input type="password" name="password"
                                            class="form-control @error('password') is-invalid @enderror"
                                            placeholder="{{ __('Minimum 4 characters') }}" required>
                                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-bold">{{ __('Confirm New Password') }} <span class="text-danger">*</span></label>
                                        <input type="password" name="password_confirmation"
                                            class="form-control" required>
                                    </div>

                                    <div class="col-12 mt-2">
                                        <button type="submit" class="btn btn-primary px-5">
                                            {{ __('Update Password') }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
