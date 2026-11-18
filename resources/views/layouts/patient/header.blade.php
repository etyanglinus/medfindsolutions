<!DOCTYPE html>
@if (session()->get('text_direction') == 'rtl')
    <html class="no-js" lang="en" dir="rtl">
@else
    <html class="no-js" lang="en">
@endif
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $appName = $setting->app_name;
        try {
            $routeName = str(Route::currentRouteName())
                ->replace('_',' - ')->replace('-',' - ')->replace('.',' - ')->title();
        } catch (\Exception $e) { $routeName = ''; }
    @endphp

    @yield('title')
    @yield('meta')
    {{-- One canonical URL per page: filters, sorting and tracking parameters are dropped; pagination is kept. --}}
    @php
        $canonicalUrl = trim($__env->yieldContent('canonical'));
        if ($canonicalUrl === '') {
            $page = (int) request()->query('page', 1);
            $canonicalUrl = url()->current().($page > 1 ? '?page='.$page : '');
        }
    @endphp
    <link rel="canonical" href="{{ $canonicalUrl }}">
    @hasSection('robots')<meta name="robots" content="@yield('robots')">@endif
    @stack('seo')

    <link rel="icon" type="image/png" href="{{ asset($setting->favicon) }}">

    @include('layouts.patient.style')
    @stack('css')

    @if(customCode()?->css)
        <style>{!! customCode()->css !!}</style>
    @endif

    {{-- Analytics and ads trackers. With the cookie banner on they load only after the visitor accepts
         (cookie mf_cookie_consent=accepted); "Decline" keeps them off. Without the banner they load as before. --}}
    @php $needsConsent = ($setting->cookie_status ?? '') === 'active'; @endphp
    <script>
        window.mfLoadTrackers = function () {
            if (window.mfTrackersLoaded) return; window.mfTrackersLoaded = true;
            @if($setting->googel_tag_status == 'active')
            (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer',@json((string) $setting->googel_tag_id));
            @endif
            @if($setting->google_analytic_status == 'active')
            (function(){var g=document.createElement('script');g.async=true;g.src='https://www.googletagmanager.com/gtag/js?id='+encodeURIComponent(@json((string) $setting->google_analytic_id));document.head.appendChild(g);window.dataLayer=window.dataLayer||[];window.gtag=function(){dataLayer.push(arguments);};gtag('js',new Date());gtag('config',@json((string) $setting->google_analytic_id));})();
            @endif
            @if($setting->pixel_status == 'active')
            !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init',@json((string) $setting->pixel_app_id));fbq('track','PageView');
            @endif
        };
        @if(!$needsConsent)
        window.mfLoadTrackers();
        @else
        if (/(?:^|;\s*)mf_cookie_consent=accepted/.test(document.cookie)) window.mfLoadTrackers();
        @endif
    </script>

    @if(customCode()?->header_javascript)
        <script>"use strict";{!! customCode()->header_javascript !!}</script>
    @endif

    <style>
        /* ── Modern Professional Navbar Styling ── */
        #mf-navbar {
            position: sticky;
            top: 0;
            z-index: 1030;
            background: #ffffff;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.08);
            /* Reduced vertical padding slightly to balance larger mobile logo */
            padding: 0.6rem 0; 
            transition: all 0.2s ease-in-out;
        }
        
        /* Base Logo Styling */
        #mf-navbar .navbar-brand img {
            height: 42px; /* Desktop height */
            width: auto;
            object-fit: contain;
            transition: height 0.2s ease; /* Smooth transition if resize happens */
        }

        /* FIX: Mobile Specific Logo Sizing */
        @media (max-width: 991.98px) {
            #mf-navbar .navbar-brand img {
                /* Force a larger height on mobile so it doesn't shrink to shrink */
                height: 38px !important; 
            }
            
            /* Ensure the brand container has zero padding to maximize logo area */
            #mf-navbar .navbar-brand {
                padding-top: 0;
                padding-bottom: 0;
                margin-right: 0.5rem; /* reduce margin to give more space */
            }
        }

        /* FIX: Extra small screen adjustment (phones in portrait) */
        @media (max-width: 350px) {
             #mf-navbar .navbar-brand img {
                height: 32px !important; /* Slightly smaller for absolute smallest screens to avoid overlapping toggle */
            }
        }

        #mf-navbar .nav-link {
            color: #475569;
            font-weight: 500;
            font-size: 0.95rem;
            padding: 0.5rem 0.85rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        #mf-navbar .nav-link:hover,
        #mf-navbar .nav-link.active-link {
            color: #0284c7;
            background-color: #f0f9ff;
        }
        #mf-navbar .btn-nav-primary {
            background-color: #0284c7;
            border-color: #0284c7;
            color: #ffffff;
            font-weight: 600;
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            transition: all 0.2s ease;
        }
        #mf-navbar .btn-nav-primary:hover {
            background-color: #0369a1;
            border-color: #0369a1;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
        }
        #mf-navbar .btn-nav-outline {
            border: 1.5px solid #cbd5e1;
            color: #334155;
            font-weight: 600;
            padding: 0.45rem 1.25rem;
            border-radius: 50px;
            transition: all 0.2s ease;
        }
        #mf-navbar .btn-nav-outline:hover {
            border-color: #0284c7;
            color: #0284c7;
            background-color: #f0f9ff;
        }
        #mf-navbar .navbar-toggler {
            border: 1px solid #e2e8f0;
            padding: 0.4rem 0.6rem;
            border-radius: 8px;
        }
        #mf-navbar .navbar-toggler:focus {
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
        }

        /* Drawer Styling */
        #mf-drawer .offcanvas-header {
            border-bottom: 1px solid #f1f5f9;
        }
        #mf-drawer .offcanvas-title img {
            height: 38px;
        }
        #mf-drawer .nav-link {
            color: #334155;
            font-weight: 500;
            font-size: 0.95rem;
            border-radius: 8px;
            padding: 0.65rem 1rem;
        }
        #mf-drawer .nav-link:hover,
        #mf-drawer .nav-link.active-link {
            color: #0284c7;
            background: #f0f9ff;
        }
        #mf-drawer .drawer-divider {
            border-top: 1px solid #f1f5f9;
            margin: 0.75rem 0;
        }
    </style>
</head>

<body>

    @if($setting->googel_tag_status == 'active' && ($setting->cookie_status ?? '') !== 'active')
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $setting->googel_tag_id }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif

    @if($setting->preloader == 1)
        <div id="preloader" class="preloader">
            <div class="status" style="background-image:url({{ url($setting->preloader_image) }})"></div>
        </div>
    @endif

    @if(customCode()?->body_javascript)
        <script>"use strict";{!! customCode()->body_javascript !!}</script>
    @endif

    {{-- STICKY HEADER --}}
    <nav id="mf-navbar" class="navbar navbar-expand-lg">
        <div class="container">

            {{-- Brand --}}
            <a class="navbar-brand me-2 me-lg-4 d-flex align-items-center" href="{{ url('/') }}" aria-label="{{ $setting->app_name }}">
                <img src="{{ asset($setting->logo) }}" alt="{{ $setting->app_name }}" loading="eager">
            </a>

            {{-- Desktop Navigation Links --}}
            <div class="collapse navbar-collapse justify-content-end" id="mf-nav-collapse">
                <ul class="navbar-nav gap-1">
                    @php
                        $navLinks = [
                            ['url' => route('home'),               'label' => __('Home')],
                            ['url' => route('website.doctors'),    'label' => __('Specialists')],
                            ['url' => route('website.facilities'), 'label' => __('Facilities')],
                            ['url' => route('website.healthcare-courses'), 'label' => __('Courses')],
                            ['url' => route('website.medical-suppliers'), 'label' => __('Suppliers')],
                            ['url' => url('blog'),                 'label' => __('Blog')],
                            ['url' => url('about-us'),             'label' => __('About Us')],
                            ['url' => url('contact-us'),           'label' => __('Contact Us')],
                        ];
                    @endphp
                    @foreach($navLinks as $link)
                        <li class="nav-item">
                            <a href="{{ $link['url'] }}"
                               class="nav-link {{ request()->url() === $link['url'] ? 'active-link' : '' }}">
                                {{ $link['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>

                {{-- Desktop User Auth Actions --}}
                @php
                    use Illuminate\Support\Str;
                    $authUser  = null;
                    $authName  = '';
                    $dashRoute = null;
                    $logoutRoute  = null;

                    if (auth('web')->check()) {
                        $authUser    = auth('web')->user();
                        $authName    = $authUser->name;
                        $dashRoute   = route('dashboard');
                        $logoutRoute = route('logout');
                    } elseif (auth('doctor')->check()) {
                        $authUser    = auth('doctor')->user();
                        $authName    = $authUser->name;
                        $dashRoute   = route('doctor.dashboard');
                        $logoutRoute = route('doctor.logout');
                    } elseif (auth('clinic')->check()) {
                        $authUser    = auth('clinic')->user();
                        $authName    = $authUser->name;
                        $dashRoute   = route('clinic.dashboard');
                        $logoutRoute = route('clinic.logout');
                    } elseif (auth('admin')->check()) {
                        $authUser    = auth('admin')->user();
                        $authName    = $authUser->name;
                        $dashRoute   = route('admin.dashboard');
                        $logoutRoute = route('admin.logout');
                    }
                @endphp

                @if($authUser)
                    <div class="d-flex align-items-center gap-2 ms-2">
                        {{-- Authenticated Dropdown --}}
                        <div class="dropdown">
                            <button class="btn btn-nav-primary dropdown-toggle d-flex align-items-center gap-2"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle fa-lg"></i>
                                <span class="d-none d-xl-inline">{{ Str::limit($authName, 18) }}</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg rounded-3 border-0 mt-2" style="min-width: 210px;">
                                <li class="px-3 py-2 bg-light border-bottom rounded-top">
                                    <div class="fw-semibold text-dark text-truncate">{{ $authName }}</div>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2 fs-6" href="{{ $dashRoute }}">
                                        <i class="fas fa-tachometer-alt text-primary fa-fw"></i>{{ __('Dashboard') }}
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li>
                                    <form method="POST" action="{{ $logoutRoute }}" id="header-logout-form" class="m-0">
                                        @csrf
                                        <button type="submit" class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger fs-6">
                                            <i class="fas fa-sign-out-alt fa-fw"></i>{{ __('Log Out') }}
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Mobile Actions & Menu Toggler --}}
            <div class="d-flex align-items-center gap-2 ms-auto d-lg-none">
                @if($authUser)
                    <a href="{{ $dashRoute }}" class="btn btn-nav-primary btn-sm px-2 px-sm-3">
                        <i class="fas fa-tachometer-alt me-1"></i>{{ __('Dashboard') }}
                    </a>
                @endif
                <button class="navbar-toggler" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#mf-drawer"
                    aria-controls="mf-drawer" aria-label="{{ __('Open menu') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>

        </div>
    </nav>

    {{-- OFF-CANVAS MOBILE DRAWER --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="mf-drawer" aria-labelledby="mf-drawer-label">
        <div class="offcanvas-header px-4 py-3">
            <span class="offcanvas-title" id="mf-drawer-label">
                <img src="{{ asset($setting->logo) }}" alt="{{ $setting->app_name }}">
            </span>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
        </div>
        <div class="offcanvas-body px-3 py-2">
            <ul class="navbar-nav flex-column gap-1">
                @foreach($navLinks as $link)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->url() === $link['url'] ? 'active-link' : '' }}" href="{{ $link['url'] }}">
                            {{ $link['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>

            @if($authUser)
                <div class="drawer-divider"></div>
                <div class="d-flex flex-column gap-2 px-1 pb-3 pt-1">
                    <a href="{{ $dashRoute }}" class="btn btn-nav-primary w-100 text-center">
                        <i class="fas fa-tachometer-alt me-2"></i>{{ __('Dashboard') }}
                    </a>
                    <form method="POST" action="{{ $logoutRoute }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger rounded-pill w-100 fw-semibold">
                            <i class="fas fa-sign-out-alt me-2"></i>{{ __('Log Out') }}
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</body>
</html>