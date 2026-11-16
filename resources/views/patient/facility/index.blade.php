@extends('layouts.patient.layout')
@section('title')<title>{{ __('Facilities & Clinics') }} – {{ $setting->app_name }}</title>@endsection
@section('meta')<meta name="description" content="{{ __('Discover verified medical facilities and clinics in Kenya — search by specialty, insurance and location.') }}">@endsection

@section('patient-content')
<div class="mf-public-page">

    {{-- ── Hero + filter bar ── --}}
    <header class="mf-directory-head">
        <div class="container">
            <span class="mf-eyebrow" style="color:#bae6fd;font-size:.7rem;font-weight:700;letter-spacing:.15em;text-transform:uppercase">
                {{ __('Kenya Facility Directory') }}
            </span>
            <h1 class="mt-1 mb-1" style="color:#fff;font-weight:800;font-size:clamp(1.6rem,3.5vw,2.4rem)">
                {{ __('Find a clinic or medical facility') }}
            </h1>
            <p style="color:#bae6fd;font-size:.88rem;margin-bottom:1.5rem">
                {{ __('Browse facilities with active subscriptions. Listings are independently verified by MedFind.') }}
            </p>

            <form method="GET" action="{{ route('website.facilities') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label class="d-block mb-1" style="color:#bae6fd;font-size:.72rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase">{{ __('Search') }}</label>
                        <input class="form-control rounded-pill px-4" style="min-height:44px"
                               name="keyword" value="{{ $keyword ?? '' }}"
                               placeholder="{{ __('Name or location…') }}">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="d-block mb-1" style="color:#bae6fd;font-size:.72rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase">{{ __('Facility type') }}</label>
                        <select class="form-select rounded-pill px-3" style="min-height:44px" name="specialty">
                            <option value="">{{ __('All types') }}</option>
                            @foreach($specialties as $spec)
                                <option value="{{ $spec }}" @selected(($specialty ?? null) === $spec)>{{ $spec }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="d-block mb-1" style="color:#bae6fd;font-size:.72rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase">{{ __('Insurance') }}</label>
                        <select class="form-select rounded-pill px-3" style="min-height:44px" name="insurance">
                            <option value="">{{ __('Any insurance') }}</option>
                            @foreach($insurances as $provider)
                                <option value="{{ $provider }}" @selected(($insurance ?? null) === $provider)>{{ $provider }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="d-block mb-1" style="color:#bae6fd;font-size:.72rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase">{{ __('Consultation fee') }}</label>
                        <select class="form-select rounded-pill px-3" style="min-height:44px" name="fee_range">
                            <option value="">{{ __('Any fee') }}</option>
                            <option value="under_2000" @selected(($feeRange ?? null) === 'under_2000')>{{ __('Under KSh 2,000') }}</option>
                            <option value="2000_5000" @selected(($feeRange ?? null) === '2000_5000')>{{ __('KSh 2,000 – 5,000') }}</option>
                            <option value="over_5000" @selected(($feeRange ?? null) === 'over_5000')>{{ __('Above KSh 5,000') }}</option>
                        </select>
                    </div>
                    <div class="col-lg-auto col-md-6 d-flex gap-2">
                        <button class="btn btn-danger rounded-pill px-4" style="min-height:44px;font-weight:600">
                            <i class="fas fa-search me-2"></i>{{ __('Search') }}
                        </button>
                        @if($keyword || $specialty || $insurance || $feeRange)
                            <a href="{{ route('website.facilities') }}"
                               class="btn btn-outline-light rounded-pill px-3" style="min-height:44px">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </header>

    {{-- ── Results ── --}}
    <div class="container py-5">

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 style="font-weight:800;color:var(--mf-ink);font-size:1.3rem;margin:0">
                    {{ __('Medical facilities') }}
                    @if($clinics->total() > 0)
                        <span style="font-size:.85rem;font-weight:500;color:var(--mf-muted);margin-left:.5rem">
                            ({{ $clinics->total() }})
                        </span>
                    @endif
                </h2>
                <p class="text-muted small mb-0">{{ __('Facilities with active subscriptions and admin approval appear here.') }}</p>
            </div>
            <a href="{{ route('provider.register') }}?type=clinic"
               class="btn btn-primary rounded-pill px-4" style="font-weight:600">
                <i class="fas fa-plus me-2"></i>{{ __('List your facility') }}
            </a>
        </div>

        <div class="row g-4">
            @forelse($clinics as $clinic)
                @php $subscription = $clinic->latestSubscription; @endphp
                <div class="col-sm-6 col-lg-4 col-xl-3">
                    <article class="mf-listing-card h-100">
                        <div class="mf-listing-card__media position-relative">
                            @include('patient.partials.featured-badge', ['item' => $clinic])
                            @if($clinic->logo)
                                <img src="{{ Str::startsWith($clinic->logo, ['http','//']) ? $clinic->logo : asset($clinic->logo) }}"
                                     alt="{{ $clinic->name }}" loading="lazy">
                            @else
                                <i class="fas fa-hospital-alt"></i>
                            @endif
                        </div>
                        <div class="mf-listing-card__body d-flex flex-column" style="min-height:170px">
                            <div class="d-flex align-items-start justify-content-between gap-1 mb-1">
                                <h3 style="font-size:.95rem;line-height:1.35">{{ $clinic->name }}</h3>
                                @if($subscription?->hasEntitlement('top_clinic_badge') || $subscription?->hasEntitlement('verified_badge'))
                                    <span class="mf-badge mf-badge--icon flex-shrink-0" title="{{ __('Verified Facility') }}">
                                        <i class="fas fa-check-circle"></i>
                                    </span>
                                @endif
                            </div>

                            <p class="mb-1">{{ $clinic->specialty ?? __('Healthcare facility') }}</p>

                            @if($clinic->location)
                                <p class="mb-1" style="font-size:.74rem">
                                    <i class="fas fa-map-marker-alt me-1" style="color:var(--mf-blue)"></i>{{ $clinic->location }}
                                </p>
                            @endif

                            @if($subscription?->hasEntitlement('emergency') && $clinic->emergency_available)
                                <span class="mf-badge mf-badge--red mb-2" style="align-self:flex-start">
                                    <i class="fas fa-bolt"></i>{{ __('24h Emergency') }}
                                </span>
                            @endif

                            <a class="btn btn-outline-primary btn-sm rounded-pill w-100 mt-auto"
                               href="{{ route('website.facility.details', $clinic->slug) }}">
                                {{ __('View facility') }} <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-12">
                    <div class="mf-empty">
                        <i class="fas fa-hospital-alt d-block mb-3"></i>
                        <h3>{{ __('No facilities found') }}</h3>
                        <p class="mb-3">{{ __('Try widening your search or clearing the filters.') }}</p>
                        @if($keyword || $specialty || $insurance || $feeRange)
                            <a href="{{ route('website.facilities') }}" class="btn btn-outline-primary rounded-pill px-4">
                                {{ __('Clear all filters') }}
                            </a>
                        @endif
                    </div>
                </div>
            @endforelse
        </div>

        @if($clinics->hasPages())
            <div class="mt-5 d-flex justify-content-center">
                {{ $clinics->links() }}
            </div>
        @endif

        {{-- ── CTA strip ── --}}
        <div class="mf-cta-strip mt-5 row align-items-center g-3">
            <div class="col-md-8">
                <h2 class="mb-1">{{ __('Are you running a clinic or medical centre?') }}</h2>
                <p>{{ __('Get listed on MedFind. Choose a visibility plan, submit for admin review, and start reaching patients in your area.') }}</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="{{ route('provider.register') }}?type=clinic"
                   class="btn btn-danger rounded-pill px-5" style="font-weight:600;min-height:48px;line-height:48px;padding-top:0;padding-bottom:0">
                    {{ __('Register now') }} <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
        </div>

    </div>
</div>
@endsection
