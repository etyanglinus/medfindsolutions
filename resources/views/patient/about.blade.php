@extends('layouts.patient.layout')
@section('title')
    <title>{{ seoSetting()->where('page_name', 'About')->first()->seo_title ?? 'About | Medfind' }}</title>
@endsection
@section('meta')
    <meta name="description"
        content="{{ seoSetting()->where('page_name', 'About')->first()->seo_description ?? 'About | Medfind' }}">
@endsection
@section('patient-content')
    <!--Banner Start-->
    <div class="banner-area flex"
        style="background-image:url({{ $setting?->breadcrumb_image ? url($setting?->breadcrumb_image) : '' }});">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="banner-text">
                        <h1>{{ __('About Us') }}</h1>
                        <ul>
                            <li><a aria-label="{{ __('Home') }}" href="{{ url('/') }}">{{ __('Home') }}</a></li>
                            <li><span>{{ __('About Us') }}</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Banner End-->


    <!--About Us Start-->
    <div class="about-style1 pt_50 pb_110">
        <div class="container">
            @if ($about && $about?->status)
                <div class="row">
                    <div class="col-lg-7">
                        <div class="about1-text sm_pr_0 pr_150 mt_30">
                            {!! $about?->about_description !!}
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="about1-bgimg mt_30"
                            style="background-image:url({{ $about?->background_image ? url($about?->background_image) : '' }});">
                            <div class="about1-inner">
                                <img src="{{ url($about?->about_image) }}" alt="{{ __('About Us') }}" loading="lazy">
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <!--About Us End-->


    @if ($about)
        <!--Mission Start-->
        <div class="mission-area bg-area pt_40 pb_90">
            <div class="container">
                @if ($about?->mission_status)
                    <div class="row align-items-center">
                        <div class="col-md-6 pt_30">
                            <div class="mission-img">
                                <img src="{{ url($about?->mission_image) }}" alt="{{ __('About Us') }}" loading="lazy">
                            </div>
                        </div>
                        <div class="col-md-6 pt_30">
                            <div class="mission-text">
                                {!! $about?->mission_description !!}



                            </div>
                        </div>
                    </div>
                @endif

                @if ($about?->vision_status)
                    <div class="row align-items-center mt_40">
                        <div class="col-md-6 pt_30">
                            <div class="mission-text">
                                {!! $about?->vision_description !!}
                            </div>
                        </div>
                        <div class="col-md-6 pt_30">
                            <div class="mission-img vision-img">
                                <img src="{{ url($about?->vision_image) }}" alt="{{ __('About Us') }}" loading="lazy">
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
        <!--Mission End-->
    @endif
    <!--Counter Start-->
    <div class="counter-page pt_40 pb_70"
        style="background-image: url({{ asset('uploads/website-images/overview-banner.webp') }})">
        <div class="container">
            <div class="row">
                @foreach ($overviews as $overview)
                    <div class="col-lg-3 col-6 mt_30 wow fadeInDown" data-wow-delay="0.2s">
                        <div class="counter-item">
                            <div class="counter-icon">
                                <img src="{{ !empty($overview?->icon) ? url($overview?->icon) : '' }}" loading="lazy">
                            </div>
                            <span class="counter counter_up">{{ $overview?->qty }}</span>
                            <p class="title">{{ $overview?->title }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <!--Counter End-->
   
@endsection
