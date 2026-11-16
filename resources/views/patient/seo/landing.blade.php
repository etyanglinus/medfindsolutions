@extends('layouts.patient.layout')
@section('title')<title>{{ $title }} | {{ $setting->app_name }}</title>@endsection
@section('meta')
<meta name="description" content="{{ $description }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url()->current() }}">
@endsection
@push('seo')
@foreach($structured as $data)@include('seo.jsonld', ['data' => $data])@endforeach
@endpush
@section('patient-content')
<main class="mf-public-page">
    <section class="mf-directory-head py-4">
        <div class="container">
            <nav aria-label="{{ __('Breadcrumb') }}" class="small mb-2" style="color:rgba(255,255,255,.85)">
                @foreach($crumbs as [$label, $url])
                    @if($url)<a href="{{ $url }}" class="text-white text-decoration-underline">{{ $label }}</a> <span aria-hidden="true">/</span> @else<span>{{ $label }}</span>@endif
                @endforeach
            </nav>
            <h1 class="h2 fw-bold mb-2">{{ $h1 }}</h1>
            <p class="mb-0" style="max-width:760px;color:rgba(255,255,255,.9)">{{ $intro }}</p>
        </div>
    </section>

    <section class="py-4">
        <div class="container">
            <div class="row g-3">
                @foreach($cards as $card)
                    <div class="col-sm-6 col-lg-4 col-xl-3">
                        <article class="mf-listing-card h-100">
                            <div class="mf-listing-card__media position-relative">
                                @include('patient.partials.featured-badge', ['item' => (object) ['plan_tier' => $card['tier']]])
                                @if($card['image'])<img src="{{ $card['image'] }}" alt="{{ $card['name'] }}" loading="lazy">@else<i class="fas fa-clinic-medical fa-3x text-primary" aria-hidden="true"></i>@endif
                            </div>
                            <div class="mf-listing-card__body">
                                <h2 class="h6 fw-bold mb-1"><a href="{{ $card['url'] }}" class="text-reset text-decoration-none">{{ $card['name'] }}</a></h2>
                                @include('patient.partials.verified-badge', ['type' => $card['type'], 'id' => $card['id']])
                                @if($card['subtitle'])<p class="small text-muted mb-1">{{ $card['subtitle'] }}</p>@endif
                                @if($card['place'])<p class="small mb-2"><i class="fas fa-map-marker-alt text-secondary me-1" aria-hidden="true"></i>{{ $card['place'] }}</p>@endif
                                <a class="btn btn-outline-primary btn-sm rounded-3 w-100 mt-2" href="{{ $card['url'] }}">{{ __('View profile') }} <i class="fas fa-arrow-right ms-1" aria-hidden="true"></i></a>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
            @if($results->hasPages())<div class="mt-4">{{ $results->links('patient.paginator') }}</div>@endif

            @foreach(array_filter([$links ?? null, $related ?? null]) as $block)
                @if(!empty($block['items']))
                    <section class="mt-5">
                        <h2 class="h5 fw-bold">{{ $block['heading'] }}</h2>
                        <ul class="list-unstyled d-flex flex-wrap gap-2 mt-3">
                            @foreach($block['items'] as $link)
                                <li><a class="btn btn-sm btn-light border rounded-pill" href="{{ $link['url'] }}">{{ $link['label'] }} <span class="text-muted">({{ $link['count'] }})</span></a></li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            @endforeach
        </div>
    </section>
</main>
@endsection
