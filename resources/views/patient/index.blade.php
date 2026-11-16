@extends('layouts.patient.layout')

@section('title')
    <title>{{ seoSetting()->where('page_name', 'Home')->first()->seo_title ?? 'Medfind' }}</title>
@endsection

@section('meta')
    <meta name="description" content="{{ seoSetting()->where('page_name', 'Home')->first()->seo_description ?? 'Medfind' }}">
@endsection

@section('patient-content')

    <!-- Compact Cross-directory Hero -->
    <section class="mf-home-hero">
        <div class="container">
            <div class="mf-home-hero__content">
                <span class="mf-home-hero__eyebrow"><i class="fas fa-compass me-2"></i>{{ __('Kenya healthcare directory') }}</span>
                <h1>{{ __('Find trusted healthcare, all in one place.') }}</h1>
                <p>{{ __('Search specialists, facilities, healthcare courses and medical suppliers with one simple search.') }}</p>
                <form id="mf-global-search" class="mf-global-search" action="{{ route('website.doctors') }}" method="GET">
                    <div class="mf-global-search__query"><i class="fas fa-search"></i><input id="mf-search-query" type="search" name="keyword" placeholder="{{ __('Search by name, service or specialty') }}" aria-label="{{ __('Search query') }}"></div>
                    <div class="mf-global-search__category"><i class="fas fa-layer-group"></i><select id="mf-search-category" name="search_type" aria-label="{{ __('Search category') }}"><option value="specialists" data-action="{{ route('website.doctors') }}" data-placeholder="{{ __('Search by name, service or specialty') }}">{{ __('Specialists') }}</option><option value="facilities" data-action="{{ route('website.facilities') }}" data-placeholder="{{ __('Search facilities by name or location') }}">{{ __('Facilities') }}</option><option value="courses" data-action="{{ route('website.healthcare-courses') }}" data-placeholder="{{ __('Search courses or institutions') }}">{{ __('Healthcare Courses') }}</option><option value="suppliers" data-action="{{ route('website.medical-suppliers') }}" data-placeholder="{{ __('Search products, brands or suppliers') }}">{{ __('Medical Suppliers') }}</option></select></div>
                    <button type="submit" class="mf-global-search__button"><span>{{ __('Search') }}</span><i class="fas fa-arrow-right"></i></button>
                </form>
                <div class="mf-home-hero__signals"><span><i class="fas fa-check-circle"></i>{{ __('Reviewed profiles') }}</span><span><i class="fas fa-bolt"></i>{{ __('Fast discovery') }}</span><span><i class="fas fa-link"></i>{{ __('Direct connections') }}</span></div>
            </div>
        </div>
    </section>

    <!-- Section 1: Featured Specialists Start -->
    @if (($home_sections?->doctor_status ?? 1) == 1 && isset($doctors) && $doctors->isNotEmpty())
        <section class="featured-specialists py-5 bg-white">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold text-dark m-0">{{ __('Featured Specialists') }}</h2>
                    </div>
                    <a href="{{ url('doctors') }}" class="text-decoration-none fw-semibold text-primary">{{ __('View all') }}</a>
                </div>

                <div class="row g-4">
                    @php 
                        $limit = $home_sections?->doctor_how_many ?? 6; 
                    @endphp
                    @foreach ($doctors->take($limit) as $doctor)
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <div class="card h-100 text-center p-3 border border-light shadow-xs rounded-4 transition-hover">
                                <div class="avatar-wrapper mb-3 position-relative d-inline-block mx-auto rounded-circle overflow-hidden bg-light" style="width: 100px; height: 100px;">
                                    <img src="{{ asset($doctor?->image ? $doctor?->image : ($setting?->default_avatar ?? 'uploads/default.png')) }}" alt="{{ $doctor?->name }}" class="img-fluid w-100 h-100 object-fit-cover" loading="lazy">
                                </div>
                                <div class="card-body p-0 d-flex flex-column justify-content-between">
                                    <div>
                                        <h5 class="fw-bold text-dark mb-1 text-truncate" style="font-size: 15px;">{{ $doctor?->name }}</h5>@include('patient.partials.verified-badge', ['type' => 'doctor', 'id' => $doctor?->id])
                                        <p class="text-muted text-xs mb-2 text-truncate" style="font-size: 13px;">{{ $doctor?->department?->name ?? __('General Specialist') }}</p>
                                        
                                        @if($doctor?->location?->name)
                                            <div class="d-flex align-items-center justify-content-center gap-1 text-xs text-muted mb-2" style="font-size: 12px;">
                                                <i class="fas fa-map-marker-alt text-secondary"></i>
                                                <span class="text-truncate">{{ ucfirst($doctor?->location?->name) }}</span>
                                            </div>
                                        @endif

                                        @if($doctor?->designations)
                                            <div class="d-flex align-items-center justify-content-center gap-1 text-xs text-muted mb-3" style="font-size: 12px;">
                                                <i class="fas fa-shield-alt text-success"></i>
                                                <span class="text-truncate" title="{{ $doctor?->designations }}">{{ $doctor?->designations }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    <a href="{{ route('website.doctor.details', $doctor?->slug ?? $doctor?->id) }}" class="btn btn-primary w-100 btn-sm rounded-3 py-2 fw-semibold mt-2" style="background-color: #0d6efd; font-size: 13px;">
                                        {{ __('Contact Specialist') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
    <!-- Section 1: Featured Specialists End -->

    <!-- Section 2: Featured Clinics Start -->
    @if (isset($featuredClinics) && $featuredClinics->isNotEmpty())
        <section class="featured-facilities py-5" style="background-color: #f8fafc;">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold text-dark m-0">{{ __('Featured Facilities') }}</h2>
                    <a href="{{ route('website.facilities') }}" class="text-decoration-none fw-semibold text-primary">{{ __('View all') }}</a>
                </div>

                <div class="row g-4">
                    @foreach ($featuredClinics->take(6) as $clinic)
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <div class="card h-100 text-center p-3 border border-light shadow-xs rounded-4 transition-hover">
                                <div class="avatar-wrapper mb-3 position-relative d-inline-block mx-auto rounded-circle overflow-hidden bg-light" style="width: 100px; height: 100px;">
                                    <img src="{{ asset($clinic?->logo ? $clinic?->logo : ($setting?->default_avatar ?? 'uploads/default.png')) }}" alt="{{ $clinic?->name }}" class="img-fluid w-100 h-100 object-fit-cover" loading="lazy">
                                </div>
                                <div class="card-body p-0 d-flex flex-column justify-content-between">
                                    <div>
                                        <h5 class="fw-bold text-dark mb-1 text-truncate" style="font-size: 15px;">{{ $clinic?->name }}</h5>@include('patient.partials.verified-badge', ['type' => 'clinic', 'id' => $clinic?->id])
                                        <p class="text-muted text-xs mb-2 text-truncate" style="font-size: 13px;">{{ $clinic?->specialty ?? __('Healthcare Clinic') }}</p>
                                        <div class="d-flex align-items-center justify-content-center gap-1 text-xs text-muted mb-3" style="font-size: 12px;">
                                            <i class="fas fa-map-marker-alt text-secondary"></i>
                                            <span class="text-truncate">{{ $clinic?->location }}</span>
                                        </div>
                                    </div>

                                    <a href="{{ route('website.facility.details', $clinic?->slug ?? $clinic?->id) }}" class="btn btn-primary w-100 btn-sm rounded-3 py-2 fw-semibold mt-2" style="background-color: #0d6efd; font-size: 13px;">
                                        {{ __('View Facility') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Section 3: Healthcare Courses -->
    <section class="py-5 mf-discovery-services mf-discovery-services--courses">
        <div class="container">
            <div class="row align-items-end g-3 mb-4">
                <div class="col-lg-8">
                    <span class="mf-eyebrow">{{ __('For students and professionals') }}</span>
                    <h2 class="fw-bold text-dark mt-2 mb-2">{{ __('Healthcare Courses') }}</h2>
                    <p class="text-muted mb-0">{{ __('Find courses and training institutions for your next healthcare career step.') }}</p>
                </div>
                <div class="col-lg-4 text-lg-end"><a href="{{ route('website.healthcare-courses') }}" class="mf-section-link">{{ __('Explore all courses') }} <i class="fas fa-arrow-right ms-2"></i></a></div>
            </div>
            @if(isset($featuredHealthcareCourses) && $featuredHealthcareCourses->isNotEmpty())
                <div class="row g-4">
                    @foreach($featuredHealthcareCourses->take(6) as $course)
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <div class="mf-feature-card h-100">
                                <div class="mf-feature-card__icon"><i class="fas fa-graduation-cap"></i></div>
                                <h3>{{ $course->name }}</h3>
                                <p>{{ $course->institution_name }}</p>
                                <span><i class="fas fa-map-marker-alt me-1"></i>{{ $course->location }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="row g-4"><div class="col-lg-8">
                    <a href="{{ route('website.healthcare-courses') }}" class="mf-discovery-card mf-discovery-card--courses h-100">
                        <div class="mf-discovery-icon"><i class="fas fa-graduation-cap"></i></div>
                        <div class="flex-grow-1">
                            <span class="mf-card-kicker">{{ __('A focused directory is coming') }}</span>
                            <h3>{{ __('Choose your path with confidence') }}</h3>
                            <p>{{ __('Explore course areas, institution information and verified education partners in one place.') }}</p>
                            <span class="mf-discovery-link">{{ __('Explore courses') }} <i class="fas fa-arrow-right ms-2"></i></span>
                        </div>
                    </a>
                </div></div>
            @endif
        </div>
    </section>

    <!-- Section 4: Medical Suppliers -->
    <section class="py-5 mf-discovery-services mf-discovery-services--suppliers">
        <div class="container">
            <div class="row align-items-end g-3 mb-4">
                <div class="col-lg-8">
                    <span class="mf-eyebrow">{{ __('For hospitals and businesses') }}</span>
                    <h2 class="fw-bold text-dark mt-2 mb-2">{{ __('Medical Suppliers') }}</h2>
                    <p class="text-muted mb-0">{{ __('Find equipment, supplies and specialist services, then connect directly with the right supplier.') }}</p>
                </div>
                <div class="col-lg-4 text-lg-end"><a href="{{ route('website.medical-suppliers') }}" class="mf-section-link">{{ __('Explore all suppliers') }} <i class="fas fa-arrow-right ms-2"></i></a></div>
            </div>
            @if(isset($featuredMedicalSuppliers) && $featuredMedicalSuppliers->isNotEmpty())
                <div class="row g-4">
                    @foreach($featuredMedicalSuppliers->take(6) as $supplier)
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <a href="{{ route('website.medical-supplier', ['slug' => $supplier->slug]) }}" class="mf-feature-card h-100 d-block text-decoration-none">
                                <div class="mf-feature-card__icon"><i class="fas fa-boxes"></i></div>
                                <h3>{{ $supplier->name }}</h3>@include('patient.partials.verified-badge', ['type' => 'supplier', 'id' => $supplier->id])
                                <p>{{ $supplier->categories->pluck('name')->implode(', ') ?: implode(', ', $supplier->supplier_types ?? []) }}</p>
                                <span><i class="fas fa-map-marker-alt me-1"></i>{{ $supplier->address }}</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="row g-4"><div class="col-lg-8">
                    <a href="{{ route('website.medical-suppliers') }}" class="mf-discovery-card mf-discovery-card--suppliers h-100">
                        <div class="mf-discovery-icon"><i class="fas fa-boxes"></i></div>
                        <div class="flex-grow-1">
                            <span class="mf-card-kicker">{{ __('A better sourcing experience') }}</span>
                            <h3>{{ __('Search by what you need') }}</h3>
                            <p>{{ __('Explore products and services by category, location and supplier type, without needing to know the company name first.') }}</p>
                            <span class="mf-discovery-link">{{ __('Explore suppliers') }} <i class="fas fa-arrow-right ms-2"></i></span>
                        </div>
                    </a>
                </div></div>
            @endif
            @if(isset($featuredSupplierProducts) && $featuredSupplierProducts->isNotEmpty())<div class="row g-3 mt-3"><div class="col-12"><h3 class="h5">{{ __('Featured supplier products') }}</h3></div>@foreach($featuredSupplierProducts as $product)<div class="col-sm-6 col-lg-4"><a class="card h-100 text-decoration-none" href="{{ route('website.medical-supplier.product', ['slug' => $product->supplier->slug, 'productSlug' => $product->slug]) }}">@if(!empty($product->supplier->public_entitlements['product_images']) && $product->image)<img class="card-img-top" src="{{ asset('uploads/store/'.$product->image) }}" alt="{{ $product->name }}" style="height:150px;object-fit:cover">@endif<div class="card-body"><h4 class="h6">{{ $product->name }}</h4><p class="small text-muted">{{ $product->supplier->name }}</p><span class="btn btn-sm btn-outline-primary">{{ __('Request a quotation') }}</span></div></a></div>@endforeach</div>@endif
        </div>
    </section>

    <!-- Trust & Value Proposition -->
    <section class="py-5 bg-white">
        <div class="container">
            <div class="text-center mx-auto mb-4" style="max-width:760px;">
                <span class="text-primary fw-semibold small text-uppercase">{{ __('Why MedFind Solutions') }}</span>
                <h2 class="fw-bold text-dark mt-2">{{ __('Kenya’s Trusted Healthcare Marketing Platform') }}</h2>
                <p class="text-muted mb-0">{{ __('MedFind Solutions connects patients with verified healthcare professionals and clinics in Nairobi and its environs.') }}</p>
            </div>
            <div class="row g-3">
                @foreach ([['fa-check-circle', __('Verified Healthcare Providers')], ['fa-search', __('Easy Search')], ['fa-id-card', __('Professional Profiles')], ['fa-shield-alt', __('Secure Platform')], ['fa-map-marked-alt', __('Nairobi & Environs Visibility')], ['fa-list-alt', __('Trusted Directory')]] as [$icon, $label])
                    <div class="col-lg-4 col-md-6">
                        <div class="border rounded-4 h-100 p-4 d-flex align-items-center gap-3 bg-light">
                            <span class="rounded-circle bg-white text-primary shadow-sm d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:46px;height:46px;"><i class="fas {{ $icon }}"></i></span>
                            <strong class="text-dark">{{ $label }}</strong>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    
    <!-- How Verification Works -->
    <section class="py-5" style="background:#f8fafc;">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-5">
                    <span class="text-primary fw-semibold small text-uppercase">{{ __('Trust & Verification') }}</span>
                    <h2 class="fw-bold text-dark mt-2">{{ __('How Verification Works') }}</h2>
                    <p class="text-muted">{{ __('Every public listing is reviewed before it is visible in the MedFind directory.') }}</p>
                </div>
                <div class="col-lg-7">
                    <div class="row g-3">
                        @foreach ([[1, __('Register your profile')], [2, __('Upload a valid practising or operating licence')], [3, __('We verify it against the relevant authority')], [4, __('Receive a verified badge and publish your profile')]] as [$step, $label])
                            <div class="col-md-6">
                                <div class="bg-white rounded-4 border p-3 h-100 d-flex gap-3 align-items-center">
                                    <span class="badge rounded-pill bg-primary px-3 py-2">{{ $step }}</span>
                                    <span class="fw-semibold text-dark">{{ $label }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Section 4: Latest From Our Blog Start -->
    @if (($home_sections?->blog_status ?? 1) == 1 && isset($blogs) && $blogs->isNotEmpty())
        <section class="blog-section py-5" style="background-color: #f8fafc;">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold text-dark m-0">
                        {{ ucfirst($home_sections?->blog_first_heading ?? 'Latest') }} {{ ucfirst($home_sections?->blog_second_heading ?? 'Articles') }}
                    </h2>
                    <a href="{{ url('blog') }}" class="text-decoration-none fw-semibold text-primary">{{ __('View all posts') }}</a>
                </div>

                <div class="row g-4">
                    <!-- Featured Horizontal Blog Post Template Block -->
                    @if (isset($feature_blog) && $feature_blog)
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 bg-white">
                                <div class="row g-0 h-100">
                                    <div class="col-sm-5 position-relative min-vh-25 bg-light">
                                        <img src="{{ asset($feature_blog?->image) }}" alt="{{ $feature_blog?->title }}" class="w-100 h-100 object-fit-cover absolute-fill" loading="lazy">
                                    </div>
                                    <div class="col-sm-7 p-4 d-flex flex-column justify-content-between">
                                        <div>
                                            <h5 class="fw-bold text-dark mb-2 line-clamp-2">{{ $feature_blog?->title }}</h5>
                                            <p class="text-muted text-xs mb-3" style="font-size: 13px;">
                                                {{ $feature_blog?->sort_description }}
                                            </p>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <span class="text-muted text-xs small"><i class="far fa-calendar-alt me-1"></i> {{ formattedDate($feature_blog?->created_at) }}</span>
                                            <a href="{{ route('website.blog.details', $feature_blog?->slug) }}" class="fw-bold text-primary text-decoration-none small">
                                                {{ __('Read More') }} &rarr;
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Iterated Loop Column Grid Block -->
                    <div class="col-lg-{{ isset($feature_blog) && $feature_blog ? '6' : '12' }}">
                        <div class="row g-3">
                            @foreach ($blogs->take(3) as $blog)
                                <div class="col-12">
                                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                                        <div class="row g-0">
                                            <div class="col-4 bg-light position-relative" style="min-height: 100px;">
                                                <img src="{{ asset($blog?->thumbnail_image ?? $blog?->image) }}" alt="{{ $blog?->title }}" class="w-100 h-100 object-fit-cover absolute-fill" loading="lazy">
                                            </div>
                                            <div class="col-8 p-3 d-flex flex-column justify-content-between">
                                                <div>
                                                    <h6 class="fw-bold text-dark mb-1 text-truncate">{{ $blog?->title }}</h6>
                                                    <p class="text-muted text-xs mb-2 text-truncate-2 small">{{ $blog?->sort_description }}</p>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-muted small" style="font-size: 11px;"><i class="far fa-calendar-alt me-1"></i> {{ formattedDate($blog?->created_at) }}</span>
                                                    <a href="{{ route('website.blog.details', $blog?->slug) }}" class="fw-bold text-primary text-decoration-none small" style="font-size: 12px;">
                                                        {{ __('Read More') }} &rarr;
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>
            </div>
        </section>
    @endif
    <!-- Section 4: Latest From Our Blog End -->

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const search = document.getElementById('mf-global-search');
            const category = document.getElementById('mf-search-category');
            const query = document.getElementById('mf-search-query');
            if (!search || !category || !query) return;
            const syncSearch = function () {
                const option = category.options[category.selectedIndex];
                search.action = option.dataset.action;
                query.placeholder = option.dataset.placeholder;
            };
            category.addEventListener('change', syncSearch);
            syncSearch();
        });
    </script>

    <!-- Extra UI Style Extensions Hook -->
    <style>
        .mf-home-hero { background: #fff url('{{ asset('uploads/website-images/dummy/doctor-1.webp') }}') right center / auto 100% no-repeat; border-bottom: 1px solid #e2e8f0; color: #102a43; min-height: 370px; padding: 3.8rem 0; position: relative; }
        .mf-home-hero::after { background: linear-gradient(90deg, #fff 0%, rgba(255,255,255,.96) 42%, rgba(255,255,255,0) 75%); content: ''; inset: 0; pointer-events: none; position: absolute; }
        .mf-home-hero .container { position: relative; z-index: 1; }
        .mf-home-hero__content { max-width: 780px; }
        .mf-home-hero__eyebrow { color: #087ea4; font-size: .72rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
        .mf-home-hero h1 { color: #102a43; font-size: clamp(2rem, 3.4vw, 3.1rem); font-weight: 800; letter-spacing: -.045em; line-height: 1.12; margin: .85rem 0 .7rem; max-width: 690px; }
        .mf-home-hero p { color: #526b7a; font-size: 1rem; line-height: 1.7; margin-bottom: 1.55rem; max-width: 620px; }
        .mf-global-search { align-items: stretch; background: #fff; border: 4px solid rgba(255,255,255,.22); border-radius: 14px; box-shadow: 0 16px 35px rgba(0,0,0,.2); display: flex; max-width: 760px; overflow: hidden; }
        .mf-global-search__query, .mf-global-search__category { align-items: center; display: flex; min-width: 0; }
        .mf-global-search__query { flex: 1 1 52%; }
        .mf-global-search__category { border-left: 1px solid #dbe5ea; flex: 0 1 28%; }
        .mf-global-search__query i, .mf-global-search__category i { color: #087ea4; margin: 0 .7rem 0 1rem; }
        .mf-global-search input, .mf-global-search select { background: transparent; border: 0; color: #102a43; font-size: .83rem; font-weight: 600; min-width: 0; outline: 0; padding: .9rem .25rem; width: 100%; }
        .mf-global-search select { cursor: pointer; }
        .mf-global-search__button { align-items: center; background: #d62839; border: 0; color: #fff; display: flex; font-size: .84rem; font-weight: 700; gap: .7rem; justify-content: center; min-width: 130px; padding: 0 1.1rem; transition: background .2s ease; }
        .mf-global-search__button:hover { background: #b91c2b; }
        .mf-home-hero__signals { color: #526b7a; display: flex; flex-wrap: wrap; font-size: .74rem; gap: 1.2rem; margin-top: 1rem; }
        .mf-home-hero__signals i { color: #d62839; margin-right: .35rem; }
        @media (max-width: 767.98px) { .mf-home-hero { background-size: auto 48%; background-position: right bottom; min-height: 0; padding: 3rem 0 12rem; } .mf-home-hero::after { background: linear-gradient(180deg, #fff 0%, rgba(255,255,255,.96) 60%, rgba(255,255,255,.2) 100%); } .mf-global-search { border-width: 3px; flex-wrap: wrap; } .mf-global-search__query, .mf-global-search__category { flex-basis: 100%; } .mf-global-search__category { border-left: 0; border-top: 1px solid #dbe5ea; } .mf-global-search__button { min-height: 46px; width: 100%; } }
        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;  
            overflow: hidden;
        }
        .transition-hover {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .transition-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.08)!important;
        }
        .absolute-fill {
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        }
        .mf-discovery-services { background: #f4f8fb; }
        .mf-discovery-services--courses { background: #f2faf9; }
        .mf-discovery-services--suppliers { background: #f4f8fb; }
        .mf-eyebrow, .mf-card-kicker { color: #0284c7; font-size: 11px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        .mf-section-link { color: #0284c7; font-size: .9rem; font-weight: 700; text-decoration: none; }
        .mf-section-link:hover { color: #0369a1; }
        .mf-discovery-card { align-items: flex-start; background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; color: #0f172a; display: flex; gap: 1.25rem; overflow: hidden; padding: 2rem; position: relative; text-decoration: none; transition: transform .2s ease, box-shadow .2s ease; }
        .mf-discovery-card::after { background: currentColor; content: ''; height: 140%; opacity: .04; position: absolute; right: -10%; top: -20%; transform: rotate(18deg); width: 35%; }
        .mf-discovery-card:hover { box-shadow: 0 18px 40px rgba(15, 23, 42, .1); color: #0f172a; transform: translateY(-4px); }
        .mf-discovery-card--courses { color: #0f766e; }
        .mf-discovery-card--suppliers { color: #0369a1; }
        .mf-discovery-icon { align-items: center; background: rgba(14, 116, 144, .1); border-radius: 14px; color: currentColor; display: flex; flex: 0 0 52px; font-size: 22px; height: 52px; justify-content: center; }
        .mf-discovery-card h3 { color: #0f172a; font-size: 1.45rem; font-weight: 700; margin: .45rem 0 .55rem; }
        .mf-discovery-card p { color: #64748b; line-height: 1.7; margin-bottom: 1.2rem; max-width: 500px; }
        .mf-discovery-link { color: currentColor; font-size: .9rem; font-weight: 700; }
        .mf-feature-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 1.25rem; transition: transform .2s ease, box-shadow .2s ease; }
        .mf-feature-card:hover { box-shadow: 0 12px 28px rgba(15, 23, 42, .08); transform: translateY(-3px); }
        .mf-feature-card__icon { align-items: center; background: #e9f7f5; border-radius: 12px; color: #0f766e; display: flex; height: 42px; justify-content: center; margin-bottom: 1rem; width: 42px; }
        .mf-discovery-services--suppliers .mf-feature-card__icon { background: #eaf4fb; color: #0369a1; }
        .mf-feature-card h3 { font-size: .95rem; font-weight: 700; line-height: 1.4; margin-bottom: .4rem; }
        .mf-feature-card p, .mf-feature-card span { color: #64748b; display: block; font-size: .76rem; margin-bottom: .45rem; }
        @media (max-width: 575.98px) { .mf-discovery-card { padding: 1.4rem; } }
    </style>

@endsection
