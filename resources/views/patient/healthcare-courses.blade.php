@extends('layouts.patient.layout')
@section('title')<title>{{ __('Healthcare Schools & Courses') }} – {{ $setting->app_name }}</title>@endsection
@section('meta')<meta name="description" content="{{ __('Find published Kenyan healthcare training institutions and admin-approved courses. Browse nursing, clinical medicine, pharmacy and more.') }}">@endsection

@section('patient-content')
<div class="mf-public-page">

    {{-- ── Hero ── --}}
    <header class="mf-page-hero">
        <div class="container text-center">
            <span class="mf-page-hero__eyebrow">{{ __('Kenya Healthcare Education Directory') }}</span>
            <h1>{{ __('Find a healthcare course or school') }}</h1>
            <p class="mx-auto">{{ __('Browse published institutions and their admin-approved courses, intake dates and accreditation information.') }}</p>

            <form method="GET" action="{{ route('website.healthcare-courses') }}" class="mt-4">
                <div class="row g-2 justify-content-center">
                    <div class="col-lg-6 col-md-8">
                        <div class="input-group" style="height:50px">
                            <input class="form-control rounded-start-pill px-4"
                                   name="q" value="{{ $term ?? '' }}"
                                   placeholder="{{ __('Search a course, school or location…') }}">
                            <button class="btn btn-danger rounded-end-pill px-4" style="font-weight:600">
                                <i class="fas fa-search me-2"></i>{{ __('Search') }}
                            </button>
                        </div>
                    </div>
                    @if(!empty($term))
                        <div class="col-auto d-flex align-items-center">
                            <a href="{{ route('website.healthcare-courses') }}"
                               class="text-white-50 text-decoration-none small">
                                <i class="fas fa-times me-1"></i>{{ __('Clear') }}
                            </a>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </header>

    <div class="container py-5">

        {{-- ── Course pill index ── --}}
        @if($publishedCourses->isNotEmpty())
            <section class="mb-5">
                <h2 class="mb-3" style="font-size:1.1rem;font-weight:700;color:var(--mf-ink)">
                    <i class="fas fa-book-open me-2" style="color:var(--mf-blue)"></i>{{ __('Browse approved courses') }}
                </h2>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($publishedCourses as $course)
                        <a class="btn btn-sm btn-outline-primary rounded-pill"
                           href="{{ route('website.healthcare-course', ['slug' => $course->slug]) }}">
                            {{ $course->name }}
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ── Institutions ── --}}
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 style="font-weight:800;color:var(--mf-ink);font-size:1.3rem;margin:0">
                    {{ __('Published institutions') }}
                    @if($institutions->total() > 0)
                        <span style="font-size:.85rem;font-weight:500;color:var(--mf-muted);margin-left:.5rem">
                            ({{ $institutions->total() }})
                        </span>
                    @endif
                </h2>
                <p class="text-muted small mb-0">{{ __('Institutions require an active subscription and admin publication to appear here.') }}</p>
            </div>
            <a href="{{ route('website.partner.register', ['type' => 'school']) }}"
               class="btn btn-primary rounded-pill px-4" style="font-weight:600">
                <i class="fas fa-plus me-2"></i>{{ __('List your institution') }}
            </a>
        </div>

        <div class="row g-4">
            @forelse($institutions as $institution)
                <div class="col-lg-6">
                    <article class="mf-school-card h-100 position-relative">
                        @include('patient.partials.featured-badge', ['item' => $institution])
                        {{-- blue top accent bar --}}
                        <div class="mf-school-card__accent"></div>

                        <div class="mf-school-card__body">
                            {{-- Header row --}}
                            <div class="d-flex gap-3 align-items-start">
                                <div class="mf-school-card__logo flex-shrink-0">
                                    @if($institution->logo)
                                        <img src="{{ asset(str_starts_with($institution->logo, 'partner-logos/') ? 'storage/'.$institution->logo : $institution->logo) }}"
                                             alt="{{ $institution->name }}">
                                    @else
                                        <i class="fas fa-graduation-cap"></i>
                                    @endif
                                </div>
                                <div class="flex-grow-1 min-width-0">
                                    <h3 class="mf-school-card__name">
                                        <a href="{{ route('website.healthcare-institution', ['slug' => $institution->slug]) }}">
                                            {{ $institution->name }}
                                        </a>
                                    </h3>
                                    <p class="mf-school-card__meta">
                                        {{ $institution->institution_type }}
                                        @if($institution->location)
                                            &nbsp;·&nbsp;<i class="fas fa-map-marker-alt me-1"></i>{{ $institution->location }}
                                        @endif
                                    </p>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        @if($institution->providerProfile?->isVerified())
                                            <span class="mf-badge mf-badge--verified">
                                                <i class="fas fa-check-circle"></i>{{ __('Verified by MedFind') }}
                                            </span>
                                        @endif
                                        @if(!empty($institution->public_entitlements['featured_intake_campaign']))
                                            <span class="mf-badge mf-badge--intake">
                                                <i class="fas fa-star"></i>{{ __('Featured institution') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Intake banner --}}
                            @if(!empty($institution->public_entitlements['intake_promotion'])
                                && $institution->intake_campaign_title
                                && (!$institution->intake_campaign_ends_at || $institution->intake_campaign_ends_at->isFuture()))
                                <div class="mf-intake-banner">
                                    <strong>{{ $institution->intake_campaign_title }}</strong>
                                    @if($institution->intake_campaign_ends_at)
                                        <small>
                                            <i class="fas fa-clock me-1"></i>
                                            {{ __('Applications close :date', ['date' => $institution->intake_campaign_ends_at->format('d M Y')]) }}
                                        </small>
                                    @endif
                                </div>
                            @endif

                            {{-- Description --}}
                            @if($institution->about)
                                <p class="text-muted mt-3 mb-2" style="font-size:.86rem;line-height:1.7">
                                    {{ Str::limit(strip_tags($institution->about), 200) }}
                                </p>
                            @endif

                            {{-- Courses --}}
                            @if($institution->public_courses->isNotEmpty())
                                <div class="mt-3">
                                    <div style="font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--mf-muted);margin-bottom:.5rem">
                                        {{ __('Courses offered') }}
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($institution->public_courses->take(5) as $course)
                                            <a href="{{ route('website.healthcare-course', ['slug' => $course->slug]) }}"
                                               class="badge bg-light border text-dark text-decoration-none"
                                               style="font-size:.75rem;font-weight:500;padding:.35rem .65rem;border-radius:999px">
                                                {{ $course->name }}
                                            </a>
                                        @endforeach
                                        @if($institution->public_courses->count() > 5)
                                            <span class="badge bg-primary text-white"
                                                  style="font-size:.75rem;padding:.35rem .65rem;border-radius:999px">
                                                +{{ $institution->public_courses->count() - 5 }} {{ __('more') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- CTA --}}
                            <div class="mt-3 pt-2 border-top d-flex gap-2 flex-wrap">
                                <a href="{{ route('website.healthcare-institution', ['slug' => $institution->slug]) }}"
                                   class="btn btn-sm btn-primary rounded-pill px-4">
                                    {{ __('View institution') }} <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                                @if(!empty($institution->public_entitlements['whatsapp']) && $institution->whatsapp)
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $institution->whatsapp) }}"
                                       target="_blank" rel="noopener"
                                       class="btn btn-sm btn-outline-success rounded-pill px-3">
                                        <i class="fab fa-whatsapp me-1"></i>{{ __('WhatsApp') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-12">
                    <div class="mf-empty">
                        <i class="fas fa-graduation-cap d-block mb-3"></i>
                        <h3>{{ __('No institutions found') }}</h3>
                        <p class="mb-3">
                            @if(!empty($term))
                                {{ __('No results for ":term". Try a different search.', ['term' => $term]) }}
                            @else
                                {{ __('No institutions are currently published.') }}
                            @endif
                        </p>
                        @if(!empty($term))
                            <a href="{{ route('website.healthcare-courses') }}"
                               class="btn btn-outline-primary rounded-pill px-4">
                                {{ __('Clear search') }}
                            </a>
                        @endif
                    </div>
                </div>
            @endforelse
        </div>

        @if($institutions->hasPages())
            <div class="mt-5 d-flex justify-content-center">
                {{ $institutions->links() }}
            </div>
        @endif

        {{-- ── Plans ── --}}
        <section class="mt-5">
            <div class="text-center mb-4">
                <span class="mf-page-hero__eyebrow" style="color:var(--mf-blue)">{{ __('School visibility plans') }}</span>
                <h2 class="mt-2" style="font-weight:800;color:var(--mf-ink)">{{ __('Reach students across Kenya') }}</h2>
                <p class="text-muted">{{ __('Choose the plan that fits your institution. Features activate immediately on payment.') }}</p>
            </div>
            <div class="row g-4 justify-content-center">
                @foreach($plans as $index => $plan)
                    <div class="col-md-4">
                        <div class="mf-plan-card {{ $index === 1 ? 'mf-plan-card--featured' : '' }} h-100">
                            @if($index === 1)
                                <div class="mb-3"><span class="mf-plan-card__badge">{{ __('Most popular') }}</span></div>
                            @endif
                            <h3>{{ $plan['name'] }}</h3>
                            <p>{{ $plan['tagline'] ?? '' }}</p>
                            <div class="mf-plan-card__price">
                                KES {{ number_format($plan['price']) }}<span>/{{ $plan['duration'] }}</span>
                            </div>
                            <ul class="mt-3">
                                @foreach($plan['features'] as $feature)
                                    <li>{{ $feature }}</li>
                                @endforeach
                            </ul>
                            <a class="btn {{ $index === 1 ? 'btn-primary' : 'btn-outline-primary' }} rounded-pill w-100"
                               href="{{ route('website.partner.register', ['type' => 'school']) }}">
                                {{ __('Get started') }} <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="text-muted text-center small mt-3">
                <i class="fas fa-info-circle me-1"></i>
                {{ __('Payment enables subscription features. Admin approval and accreditation verification are separate steps.') }}
            </p>
        </section>

        {{-- ── CTA strip ── --}}
        <div class="mf-cta-strip mt-5 row align-items-center g-3">
            <div class="col-md-8">
                <h2 class="mb-1">{{ __('Are you a healthcare training institution?') }}</h2>
                <p>{{ __('Register on MedFind, choose a visibility plan, and start connecting with students, nurses, clinicians and healthcare professionals across Kenya.') }}</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="{{ route('website.partner.register', ['type' => 'school']) }}"
                   class="btn btn-danger rounded-pill px-5"
                   style="font-weight:600;min-height:48px;line-height:48px;padding-top:0;padding-bottom:0">
                    {{ __('Register your institution') }} <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
        </div>

    </div>{{-- /container --}}
</div>{{-- /mf-public-page --}}
@endsection
