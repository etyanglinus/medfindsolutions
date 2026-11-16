@extends('layouts.patient.layout')
@section('title')
    <title>{{ __('Complete Doctor Registration') }}</title>
@endsection

@push('css')
<style>
    .plan-card { border:2px solid #dee2e6; border-radius:12px; transition:border-color .2s,box-shadow .2s; cursor:pointer; height:100%; }
    .plan-card:hover,.plan-card.selected { border-color:#0d6efd; box-shadow:0 4px 16px rgba(13,110,253,.18); }
    .plan-card .plan-header { border-radius:10px 10px 0 0; padding:18px 16px 14px; }
    .plan-card .plan-price  { font-size:1.6rem; font-weight:700; line-height:1; }
    .plan-card .plan-duration { font-size:.82rem; opacity:.85; }
    .plan-card .plan-features li { font-size:.9rem; padding:3px 0; }
    .plan-card .plan-features li::before { content:"✓ "; color:#198754; font-weight:700; }
    .plan-basic     .plan-header { background:#e8f0fe; color:#1a3c8f; }
    .plan-premium   .plan-header { background:#e6f9f0; color:#145c3a; }
    .plan-executive .plan-header { background:#fff8e1; color:#7a5c00; }
    .payment-tab-btn { border:2px solid #dee2e6; border-radius:10px; padding:14px 20px; cursor:pointer; background:#fff; transition:all .2s; width:100%; text-align:left; }
    .payment-tab-btn.active { border-color:#0d6efd; background:#f0f6ff; }
    .payment-tab-btn .gateway-logo { height:28px; object-fit:contain; }
</style>
@endpush

@section('patient-content')
    @include('patient.profile.auth._hero', ['title' => __('Complete Registration'), 'breadcrumb' => __('Choose Plan')])

    <div class="py-5" style="background:#f8f9fa;">
        <div class="container wow fadeIn">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10 col-xl-9">

                    {{-- Status alerts --}}
                    @if(! $doctor->email_verified_at)
                        <div class="alert alert-warning">
                            <p>{{ __('Please verify your email address. If the email did not arrive, request another below.') }}</p>
                            <form method="POST" action="{{ route('doctor.registration.verification.resend', $doctor->registration_token) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-primary">{{ __('Resend verification') }}</button>
                            </form>
                        </div>
                    @endif
                    @if($subscription?->status === 'approved' && $subscription->isActive())
                        <div class="alert alert-success rounded-3">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ __('Your') }} <strong>{{ $subscription->package_name }}</strong> {{ __('plan is active until') }} {{ $subscription->expires_at?->format('d M Y') ?? __('further notice') }}.
                            {{ __('You can renew or upgrade early below — the new period starts when the current one ends.') }}
                        </div>
                    @elseif($subscription?->status === 'approved')
                        <div class="alert alert-warning rounded-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            {{ __('Your') }} <strong>{{ $subscription->package_name }}</strong> {{ __('plan expired on') }} {{ $subscription->expires_at?->format('d M Y') }}.
                            {{ __('Choose a plan below and pay with Paystack to restore your listing.') }}
                        </div>
                    @elseif($subscription?->status === 'pending')
                        <div class="alert alert-info rounded-3 d-flex gap-2 align-items-center">
                            <i class="fas fa-hourglass-half"></i>
                            <span>{{ __('An earlier payment for the') }} <strong>{{ $subscription->package_name }}</strong> {{ __('package is still awaiting confirmation. If you already paid, there is no need to pay again; otherwise pay below.') }}</span>
                        </div>
                    @elseif($subscription?->status === 'rejected')
                        <div class="alert alert-danger rounded-3">
                            <i class="fas fa-times-circle me-2"></i>
                            {{ __('Your previous payment was rejected.') }}
                            @if($subscription->admin_note) — {{ $subscription->admin_note }} @endif
                        </div>
                    @endif

                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="card-header bg-white border-0 pt-4 pb-3 px-4 px-sm-5 text-center">
                            <h4 class="fw-bold mb-1">{{ __('Choose Your Plan') }}</h4>
                            <p class="text-muted small mb-0">{{ __('Select a package and pay securely with Paystack.') }}</p>
                        </div>

                        <div class="card-body px-4 px-sm-5 pb-5">

                            {{-- ── Plan cards ── --}}
                            <div class="row mb-4" id="planCards">
                                @foreach($packages as $package)
                                    @php
                                        $colorClass = match($package['id']) {
                                            'premium'   => 'plan-premium',
                                            'executive' => 'plan-executive',
                                            default     => 'plan-basic',
                                        };
                                        $isSelected = old('package', $loop->first ? $package['id'] : '') === $package['id'];
                                    @endphp
                                    <div class="col-md-4 mb-3">
                                        <label for="pkg_{{ $package['id'] }}" class="d-block h-100" style="cursor:pointer;">
                                            <div class="plan-card {{ $colorClass }} {{ $isSelected ? 'selected' : '' }}" id="card_{{ $package['id'] }}">
                                                <div class="plan-header text-center">
                                                    <div class="fw-bold fs-5">{{ $package['name'] }}</div>
                                                    <div class="plan-price mt-1">KSh {{ number_format($package['price']) }}</div>
                                                    <div class="plan-duration">{{ $package['duration'] }}</div>
                                                    <div class="fst-italic mt-1" style="font-size:.8rem;">{{ $package['tagline'] }}</div>
                                                </div>
                                                <div class="p-3">
                                                    <ul class="list-unstyled plan-features mb-3">
                                                        @foreach($package['features'] as $feat)
                                                            <li>{{ $feat }}</li>
                                                        @endforeach
                                                    </ul>
                                                    <div class="text-center">
                                                        <input class="form-check-input plan-radio" type="radio"
                                                            name="package" id="pkg_{{ $package['id'] }}"
                                                            value="{{ $package['id'] }}"
                                                            data-price="{{ $package['price'] }}"
                                                            data-name="{{ $package['name'] }}"
                                                            {{ $isSelected ? 'checked' : '' }}>
                                                        <label class="form-check-label ms-1" for="pkg_{{ $package['id'] }}">{{ __('Select') }}</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @error('package')<div class="text-danger mb-3 small">{{ $message }}</div>@enderror

                            <hr class="my-4">

                            @include('provider.paystack-form', ['checkoutType' => 'doctor', 'checkoutToken' => $doctor->registration_token])
</div>{{-- /card-body --}}
                    </div>{{-- /card --}}

                    <div class="text-center mt-4">
                        <a href="{{ route('login', ['type' => 'doctor']) }}" class="btn btn-outline-secondary rounded-pill px-4">
                            {{ __('Go to Login') }}
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
@include('provider.paystack-script')
@endpush
