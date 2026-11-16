@extends('layouts.patient.layout')
@section('title')<title>{{ __('Choose your package') }} – {{ $setting->app_name }}</title>@endsection

@section('patient-content')
<div class="mf-public-page" style="background:var(--mf-soft);min-height:100vh">

    {{-- ── Progress header ── --}}
    <header class="mf-page-hero" style="padding:3rem 0 2.5rem">
        <div class="container text-center">
            <div class="d-flex justify-content-center gap-3 mb-3">
                <span style="font-size:.78rem;font-weight:600;color:#bae6fd;opacity:.6">
                    <span style="background:rgba(255,255,255,.25);border-radius:999px;width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center;margin-right:.4rem">1</span>
                    {{ __('Create account') }}
                </span>
                <span style="color:rgba(255,255,255,.3);font-size:.8rem">›</span>
                <span style="font-size:.78rem;font-weight:700;color:#fff">
                    <span style="background:var(--mf-red);border-radius:999px;width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center;margin-right:.4rem">2</span>
                    {{ __('Choose package') }}
                </span>
            </div>
            <h1 style="font-size:clamp(1.6rem,3vw,2.2rem)">{{ __('Choose your visibility package') }}</h1>
            <p class="mx-auto" style="max-width:560px">
                {{ __('Your subscription activates the features in your plan immediately. Admin publication and verification are reviewed separately.') }}
            </p>
        </div>
    </header>

    <div class="container py-5">

        @if(session('message'))
            <div class="alert alert-success rounded-3 mb-4">{{ session('message') }}</div>
        @endif

        {{-- ── Plan picker ── --}}
        <div class="mf-plan-picker mb-4" id="plan-picker">
            @foreach($packages as $package)
                <label class="mf-plan-option">
                    <input type="radio" name="__plan_ui" value="{{ $package['id'] }}"
                           data-id="{{ $package['id'] }}"
                           data-price="{{ (int) round($package['price'] * 100) }}"
                           data-label="{{ $package['name'] }} · KES {{ number_format($package['price']) }}"
                           @checked($loop->first)>
                    <div class="mf-plan-option__card">
                        <div class="mf-plan-option__name">{{ $package['name'] }}</div>
                        <div style="font-size:.78rem;color:var(--mf-muted);margin:.15rem 0 .6rem">{{ $package['tagline'] ?? '' }}</div>
                        <div class="mf-plan-option__price">
                            KES {{ number_format($package['price']) }}
                            <small>/{{ $package['duration'] }}</small>
                        </div>
                        <ul class="mf-plan-option__features">
                            @foreach($package['features'] as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>
                    </div>
                </label>
            @endforeach
        </div>

        {{-- ── Payment section ── --}}
        <div class="mf-pay-section" style="max-width:680px;margin:0 auto">

            {{-- Coupon --}}
            <div class="mb-4">
                <label class="form-label fw-600" style="font-size:.82rem;color:var(--mf-ink)">
                    {{ __('Coupon code') }} <span class="text-muted fw-normal">({{ __('optional') }})</span>
                </label>
                <div class="input-group" style="max-width:360px">
                    <input class="form-control rounded-start-pill px-4" id="coupon-input"
                           maxlength="60" placeholder="{{ __('e.g. LAUNCH50') }}">
                    <button type="button" class="btn btn-outline-primary rounded-end-pill px-4"
                            id="retired-coupon-preview" style="font-weight:600">
                        {{ __('Apply') }}
                    </button>
                </div>
                <div id="coupon-msg" class="small mt-1"></div>
            </div>

            {{-- ┄ Paystack ── (shown only when enabled) ── --}}
            @include('provider.paystack-form', ['checkoutType' => $type, 'checkoutToken' => $record->registration_token, 'existingCoupon' => true])

        </div>

        <p class="text-muted text-center small mt-4">
            <i class="fas fa-shield-alt me-1"></i>
            {{ __('All payments are verified before your listing is activated. Need help? Contact us.') }}
        </p>

    </div>{{-- /container --}}
</div>

@include('provider.paystack-script')
@endsection
