<!-- Stylesheets -->
<link rel="stylesheet" href="{{ asset('backend/css/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('patient/css/nice-select.css') }}">
<link rel="stylesheet" href="{{ asset('patient/fontawesome-free/css/all.min.css') }}">
<link rel="stylesheet" href="{{ asset('patient/css/animate.min.css') }}">
<link rel="stylesheet" href="{{ asset('patient/css/magnific-popup.css') }}">
<link rel="stylesheet" href="{{ asset('patient/css/jquery-ui.min.css') }}">
<link rel="stylesheet" href="{{ asset('patient/css/owl.carousel.min.css') }}">
<link rel="stylesheet" href="{{ asset('patient/css/swiper-bundle.css') }}">
<link rel="stylesheet" href="{{ asset('patient/css/slick.css') }}">
<link rel="stylesheet" href="{{ asset('patient/css/datatable.min.css') }}">
<link rel="stylesheet" href="{{ asset('patient/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('patient/css/spacing.css') }}">
<link rel="stylesheet" href="{{ asset('patient/css/bootstrap-datepicker.min.css') }}">
<link rel="stylesheet" href="{{ asset('patient/css/cookie-consent.css') }}">
<link rel="stylesheet" href="{{ asset('patient/css/prescription.css') }}?v={{ $setting?->version }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">



<link rel="stylesheet" href="{{ asset('patient/css/style.css') }}?v={{ $setting?->version }}">
@if (session()->get('text_direction') == 'rtl')
    <link rel="stylesheet" href="{{ asset('patient/css/rtl.css') }}?v={{ $setting?->version }}">
@endif
<link rel="stylesheet" href="{{ asset('patient/css/responsive.css') }}?v={{ $setting?->version }}">
<link rel="stylesheet" href="{{ asset('patient/css/medfind-public.css') }}?v={{ $setting?->version }}">


<link rel="stylesheet" href="{{ asset('global/toastr/toastr.min.css') }}">
<link rel="stylesheet" href="{{ asset('patient/css/jquery-ui.css') }}">

<style>
    :root {
        --colorPrimary: {{ $setting->theme_one }} !important;
        --colorSecondary: {{ $setting->theme_two }} !important;
    }

    body, button, input, select, textarea {
        font-family: 'Poppins', sans-serif;
    }

    .banner-area .banner-text ul,
    nav[aria-label="breadcrumb"] {
        display: none !important;
    }

    @if ($setting->tawk_status != 'active')
        .scroll-top {
            bottom: 26px;
        }
    @endif
</style>
