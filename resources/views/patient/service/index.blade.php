@extends('layouts.patient.layout')
@section('title')
    <title>{{ seoSetting()->where('page_name', 'Service')->first()?->seo_title ?? 'Service | Medfind' }}</title>
@endsection
@section('meta')
    <meta name="description"
        content="{{ seoSetting()->where('page_name', 'Service')->first()?->seo_description ?? 'Service | Medfind' }}">
@endsection
@section('patient-content')
    <!--Banner Start-->
    <div class="banner-area flex"
        style="background-image:url({{ $setting?->breadcrumb_image ? url($setting?->breadcrumb_image) : '' }});">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="banner-text">
                        <h1>{{ __('Services') }}</h1>
                        <ul>
                            <li><a aria-label="{{ __('Home') }}" href="{{ url('/') }}">{{ __('Home') }}</a></li>
                            <li><span>{{ __('Services') }}</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Banner End-->

    <div class="service-area bg-area">
        <div class="container">
            <div class="row service-row">
                <div class="col-md-12">
                    <div class="service-coloum-area">
                        @foreach ($services as $service)
                            <div class="service-coloum">
                                <div class="service-item">
                                    <img src="{{ !empty($service->icon) ? url($service->icon) : '' }}" alt="{{$service?->title }}" loading="lazy">
                                    <a href="{{ route('website.service.details', $service?->slug) }}">
                                        <h4 class="title">{{ $service?->title }}</h4>
                                    </a>
                                    <p>{{ $service?->sort_description }}</p>
                                    <a aria-label="{{ __('Service Details') }}" href="{{ route('website.service.details', $service?->slug) }}">{{ __('Service Details') }}
                                        →</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @if ($services->hasPages())
                {{ $services->links('patient.paginator') }}
            @endif
        </div>
    </div>
@endsection
