@extends('layouts.patient.layout')
@section('title')
    <title>{{ __('Choose Clinic Package') }}</title>
@endsection

@push('css')
<style>
    .plan-card {
        border: 2px solid #dee2e6;
        border-radius: 12px;
        transition: border-color 0.2s, box-shadow 0.2s;
        cursor: pointer;
        height: 100%;
    }
    .plan-card:hover { border-color: #0d6efd; box-shadow: 0 4px 16px rgba(13,110,253,0.12); }
    .plan-card.selected { border-color: #0d6efd; box-shadow: 0 4px 16px rgba(13,110,253,0.18); }
    .plan-card .plan-header { border-radius: 10px 10px 0 0; padding: 18px 16px 14px; }
    .plan-card .plan-price { font-size: 1.6rem; font-weight: 700; line-height: 1; }
    .plan-card .plan-duration { font-size: 0.82rem; opacity: 0.85; }
    .plan-card .plan-features li { font-size: 0.9rem; padding: 3px 0; }
    .plan-card .plan-features li::before { content: "✓ "; color: #198754; font-weight: 700; }
    .plan-basic   .plan-header { background: #e8f0fe; color: #1a3c8f; }
    .plan-premium .plan-header { background: #e6f9f0; color: #145c3a; }
    .plan-executive .plan-header { background: #fff8e1; color: #7a5c00; }
</style>
@endpush

@section('patient-content')
    <div class="banner-area flex"
        style="background-image:url({{ $setting?->breadcrumb_image ? url($setting?->breadcrumb_image) : '' }});">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="banner-text">
                        <h1>{{ __('Complete Clinic Registration') }}</h1>
                        <ul>
                            <li><a href="{{ url('/') }}">{{ __('Home') }}</a></li>
                            <li><span>{{ __('Choose Package') }}</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="register-area pt_70 pb_70">
        <div class="container wow fadeIn">
            <div class="row justify-content-center">
                <div class="col-lg-11">
                    <div class="login_area_bg">
                        <div class="section-title text-center mb-4">
                            <h2>{{ __('MEDFIND SOLUTIONS – Clinic Listing Packages') }}</h2>
                            <p>{{ __('Select your preferred package and provide payment details. Your clinic will be listed after admin approval.') }}</p>
                        </div>

                        @if ($subscription?->status === 'pending')
                            <div class="alert alert-info">
                                {{ __('You have already submitted a payment request for the') }}
                                <strong>{{ $subscription->package_name }}</strong> {{ __('package.') }}
                                {{ __('Please wait for admin approval.') }}
                            </div>
                        @elseif ($subscription?->status === 'approved')
                            <div class="alert alert-success">
                                {{ __('Your payment has been approved. Your clinic will be activated shortly.') }}
                            </div>
                        @elseif ($subscription?->status === 'rejected')
                            <div class="alert alert-danger">
                                {{ __('Your previous payment request was rejected. Please submit a new one.') }}
                                @if($subscription->admin_note)
                                    <br><strong>{{ __('Reason:') }}</strong> {{ $subscription->admin_note }}
                                @endif
                            </div>
                        @endif

                        @if (true)
                        
                        <form action="{{ route('provider.paystack.start', ['type' => 'clinic', 'token' => $clinic->registration_token]) }}" method="POST" class="mf-paystack-form" data-provider-type="clinic" data-registration-token="{{ $clinic->registration_token }}">
                            @csrf

                            {{-- Plan cards --}}
                            <div class="row mb-4">
                                @foreach ($packages as $package)
                                    @php
                                        $colorClass = match($package['id']) {
                                            'basic'     => 'plan-basic',
                                            'premium'   => 'plan-premium',
                                            'executive' => 'plan-executive',
                                            default     => 'plan-basic',
                                        };
                                        $isSelected = old('package', $loop->first ? $package['id'] : '') === $package['id'];
                                    @endphp
                                    <div class="col-md-4 mb-3">
                                        <label for="package_{{ $package['id'] }}" class="d-block h-100" style="cursor:pointer;">
                                            <div class="plan-card {{ $colorClass }} {{ $isSelected ? 'selected' : '' }}" id="card_{{ $package['id'] }}">
                                                <div class="plan-header text-center">
                                                    <div class="fw-bold fs-5">{{ $package['name'] }}</div>
                                                    <div class="plan-price mt-1">KSh {{ number_format($package['price']) }}</div>
                                                    <div class="plan-duration">{{ $package['duration'] }}</div>
                                                    <div class="fst-italic mt-1" style="font-size:0.8rem;">{{ $package['tagline'] }}</div>
                                                </div>
                                                <div class="p-3">
                                                    <ul class="list-unstyled plan-features mb-3">
                                                        @foreach($package['features'] as $feature)
                                                            <li>{{ $feature }}</li>
                                                        @endforeach
                                                    </ul>
                                                    <div class="text-center">
                                                        <input class="form-check-input plan-radio" type="radio"
                                                            name="package" id="package_{{ $package['id'] }}"
                                                            value="{{ $package['id'] }}"
                                                            data-price="{{ $package['price'] }}"
                                                            {{ $isSelected ? 'checked' : '' }}>
                                                        <label class="form-check-label ms-1" for="package_{{ $package['id'] }}">
                                                            {{ __('Select this plan') }}
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @error('package')<div class="text-danger mb-3">{{ $message }}</div>@enderror

                            <hr>
                            @include('provider.paystack-button')
</form>
                        @else
                            <div class="alert alert-secondary">
                                {{ __('Continue to Paystack to complete your subscription.') }}
                            </div>
                        @endif

                        <div class="mt-4 text-center">
                            <a href="{{ route('login') }}" class="btn btn-outline-secondary">
                                {{ __('Go to Login') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
@include('provider.paystack-script')
@endpush
