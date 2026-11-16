@extends('layouts.patient.layout')
@section('title')<title>{{ __('Medical Suppliers') }} – {{ $setting->app_name }}</title>@endsection
@section('meta')<meta name="description" content="{{ __('Find approved healthcare suppliers, products, categories and brands in Kenya. Request quotations directly.') }}">@endsection

@section('patient-content')
<div class="mf-public-page">

    {{-- ── Hero ── --}}
    <header class="mf-page-hero">
        <div class="container text-center">
            <span class="mf-page-hero__eyebrow">{{ __('Kenya Healthcare Supplier Directory') }}</span>
            <h1>{{ __('Find products. Connect with suppliers.') }}</h1>
            <p class="mx-auto">{{ __('Browse published suppliers and approved products, then request a quotation directly.') }}</p>

            <form method="GET" action="{{ route('website.medical-suppliers') }}" class="mt-4">
                <div class="row g-2 justify-content-center">
                    <div class="col-lg-4 col-md-6">
                        <input class="form-control rounded-pill px-4" style="min-height:48px"
                               name="q" value="{{ $term }}"
                               placeholder="{{ __('Supplier, product or brand…') }}">
                    </div>
                    <div class="col-lg-2 col-md-3 col-6">
                        <select class="form-select rounded-pill px-3" style="min-height:48px" name="category">
                            <option value="">{{ __('All categories') }}</option>
                            @foreach($categories as $item)
                                <option value="{{ $item->slug }}" @selected($category === $item->slug)>{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-3 col-6">
                        <select class="form-select rounded-pill px-3" style="min-height:48px" name="county">
                            <option value="">{{ __('All counties') }}</option>
                            @foreach($counties as $item)
                                <option @selected($county === $item)>{{ $item }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-auto col-md-auto col-12">
                        <button class="btn btn-danger rounded-pill px-4 w-100" style="min-height:48px;font-weight:600">
                            <i class="fas fa-search me-2"></i>{{ __('Search') }}
                        </button>
                    </div>
                </div>
                <div class="row g-2 justify-content-center mt-1">
                    <div class="col-lg-2 col-md-4 col-6">
                        <select class="form-select rounded-pill px-3" style="min-height:42px;font-size:.82rem" name="brand">
                            <option value="">{{ __('All brands') }}</option>
                            @foreach($brands as $item)
                                <option @selected($brand === $item)>{{ $item }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6">
                        <select class="form-select rounded-pill px-3" style="min-height:42px;font-size:.82rem" name="supplier_type">
                            <option value="">{{ __('All supplier types') }}</option>
                            @foreach($supplierTypes as $item)
                                <option @selected($supplierType === $item)>{{ $item }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($term || $category || $county || $brand || $supplierType)
                        <div class="col-auto d-flex align-items-center">
                            <a href="{{ route('website.medical-suppliers') }}" class="text-white-50 small text-decoration-none">
                                <i class="fas fa-times me-1"></i>{{ __('Clear filters') }}
                            </a>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </header>

    <div class="container py-5">

        {{-- ── Approved products ── --}}
        @if($products->isNotEmpty())
        <section class="mb-5">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <h2 class="fw-800 mb-0" style="color:var(--mf-ink);font-weight:800">{{ __('Approved products') }}</h2>
                    <p class="text-muted small mb-0">{{ __('Contact suppliers to discuss availability and pricing.') }}</p>
                </div>
            </div>
            <div class="row g-3">
                @foreach($products as $product)
                <div class="col-6 col-md-4 col-lg-3">
                    <article class="mf-product-card h-100">
                        <div class="mf-product-card__img">
                            @if($product->image)
                                <img src="{{ asset('uploads/store/'.$product->image) }}" alt="{{ $product->name }}" loading="lazy">
                            @else
                                <i class="fas fa-box-open"></i>
                            @endif
                        </div>
                        <div class="mf-product-card__body">
                            @if($product->category)
                                <div class="mf-product-card__cat">{{ $product->category->name }}</div>
                            @endif
                            <h3 class="mf-product-card__name">
                                <a href="{{ route('website.medical-supplier.product', ['slug' => $product->supplier->slug, 'productSlug' => $product->slug]) }}">{{ $product->name }}</a>
                            </h3>
                            @if($product->brand && !empty($product->public_entitlements['brands']))
                                <p class="small text-muted mb-1">{{ $product->brand }}</p>
                            @endif
                            <p class="small text-muted mb-2">{{ $product->supplier->name }}</p>
                            <a class="btn btn-sm btn-outline-primary rounded-pill w-100"
                               href="{{ route('website.medical-supplier.product', ['slug' => $product->supplier->slug, 'productSlug' => $product->slug]) }}">
                                {{ __('View product') }}
                            </a>
                        </div>
                    </article>
                </div>
                @endforeach
            </div>
            @if($products->hasPages())
                <div class="mt-4">{{ $products->links() }}</div>
            @endif
        </section>
        @endif

        {{-- ── Published suppliers ── --}}
        <section class="mb-5">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <h2 class="fw-800 mb-0" style="color:var(--mf-ink);font-weight:800">{{ __('Published suppliers') }}</h2>
                    <p class="text-muted small mb-0">{{ __('Active subscriptions · admin-published listings') }}</p>
                </div>
                <a class="btn btn-primary rounded-pill px-4" href="{{ route('website.partner.register', ['type' => 'supplier']) }}">
                    <i class="fas fa-plus me-2"></i>{{ __('List your company') }}
                </a>
            </div>

            <div class="row g-4">
                @forelse($suppliers as $supplier)
                <div class="col-lg-6">
                    <article class="mf-supplier-card h-100 position-relative">
                        @include('patient.partials.featured-badge', ['item' => $supplier])
                        <div class="mf-supplier-card__body">
                            <div class="d-flex gap-3 align-items-start">
                                <div class="mf-supplier-card__logo flex-shrink-0">
                                    @if($supplier->logo)
                                        <img src="{{ asset(str_starts_with($supplier->logo,'partner-logos/') ? 'uploads/store/'.$supplier->logo : $supplier->logo) }}" alt="{{ $supplier->name }}">
                                    @else
                                        <i class="fas fa-boxes"></i>
                                    @endif
                                </div>
                                <div class="flex-grow-1 min-width-0">
                                    <h3 class="mf-supplier-card__name">
                                        <a href="{{ route('website.medical-supplier', ['slug' => $supplier->slug]) }}">{{ $supplier->name }}</a>
                                    </h3>
                                    <p class="mf-supplier-card__sub">{{ implode(' · ', $supplier->supplier_types ?? []) }}</p>
                                    <div class="d-flex flex-wrap gap-1">
                                        @include('patient.partials.verified-badge', ['type' => 'supplier', 'id' => $supplier->id])
                                        @foreach(array_slice($supplier->categories->pluck('name')->all(), 0, 3) as $cat)
                                            <span class="mf-badge">{{ $cat }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            @if($supplier->promotional_banner)
                                <div class="mt-3">
                                    <img src="{{ asset('uploads/store/'.$supplier->promotional_banner) }}"
                                         alt="{{ __('Promotion') }}" class="img-fluid rounded-3">
                                </div>
                            @endif

                            @if($supplier->description)
                                <p class="mf-supplier-card__desc mt-3 mb-2">{{ Str::limit(strip_tags($supplier->description), 160) }}</p>
                            @endif

                            @if($supplier->public_products->isNotEmpty())
                                <div class="mt-2 mb-3">
                                    <div class="small fw-600 text-muted text-uppercase mb-2" style="font-size:.7rem;letter-spacing:.07em">{{ __('Products') }}</div>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($supplier->public_products->take(4) as $product)
                                            <a href="{{ route('website.medical-supplier.product', ['slug' => $supplier->slug, 'productSlug' => $product->slug]) }}"
                                               class="badge bg-light text-dark border text-decoration-none" style="font-size:.75rem;font-weight:500">
                                                {{ $product->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <a href="{{ route('website.medical-supplier', ['slug' => $supplier->slug]) }}"
                               class="btn btn-primary rounded-pill px-4 btn-sm mt-1">
                                {{ __('View profile & request quote') }} <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </article>
                </div>
                @empty
                <div class="col-12">
                    <div class="mf-empty">
                        <i class="fas fa-boxes d-block mb-3"></i>
                        <h3>{{ __('No suppliers found') }}</h3>
                        <p class="mb-0">{{ __('Try widening your search or clearing the filters.') }}</p>
                    </div>
                </div>
                @endforelse
            </div>

            @if($suppliers->hasPages())
                <div class="mt-4">{{ $suppliers->links() }}</div>
            @endif
        </section>

        {{-- ── Pricing plans ── --}}
        <section class="mt-3">
            <div class="text-center mb-4">
                <span class="mf-page-hero__eyebrow" style="color:var(--mf-blue)">{{ __('Supplier visibility plans') }}</span>
                <h2 class="mt-2" style="font-weight:800;color:var(--mf-ink)">{{ __('Start reaching healthcare buyers') }}</h2>
                <p class="text-muted">{{ __('Choose the plan that fits your business. Payment unlocks subscription features instantly.') }}</p>
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
                        <div class="mf-plan-card__price">KES {{ number_format($plan['price']) }}<span>/{{ $plan['duration'] }}</span></div>
                        <ul class="mt-3">
                            @foreach($plan['features'] as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>
                        <a class="btn {{ $index === 1 ? 'btn-primary' : 'btn-outline-primary' }} rounded-pill w-100"
                           href="{{ route('website.partner.register', ['type' => 'supplier']) }}">
                            {{ __('Get started') }} <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
            <p class="text-muted text-center small mt-3">
                <i class="fas fa-info-circle me-1"></i>
                {{ __('Payment unlocks subscription features. Admin publication and verification are separate steps.') }}
            </p>
        </section>

        {{-- ── CTA ── --}}
        <div class="mf-cta-strip mt-5 row align-items-center g-3">
            <div class="col-md-8">
                <h2 class="mb-1">{{ __('Are you a healthcare supplier?') }}</h2>
                <p>{{ __('Register your company, choose a plan, and start reaching hospitals, clinics and procurement teams across Kenya.') }}</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="{{ route('website.partner.register', ['type' => 'supplier']) }}"
                   class="btn btn-danger rounded-pill px-5 fw-600">
                    {{ __('Register now') }} <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
        </div>

    </div>{{-- /container --}}
</div>{{-- /mf-public-page --}}
@include('seo.browse-links', ['browseType' => 'supplier'])
@endsection
