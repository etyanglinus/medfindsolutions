@php
    $header_clinic = Auth::guard('clinic')->user();
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @yield('title')
    <link rel="icon" href="{{ asset($setting->favicon) }}">
    @include('backend_layouts.partials.styles')
    @stack('css')
</head>

<body>
    <div id="app" class="doctor_dashboard_layout">
        <div class="main-wrapper">
            <div class="navbar-bg"></div>

            {{-- Top navbar --}}
            <nav class="navbar navbar-expand-lg main-navbar px-3 py-2">
                <div class="me-auto form-inline">
                    <ul class="me-3 navbar-nav d-flex align-items-center">
                        <li>
                            <a href="#" data-toggle="sidebar" class="nav-link nav-link-lg">
                                <i class="fas fa-bars"></i>
                            </a>
                        </li>
                        @include('backend_layouts.partials.language_select')
                        @include('backend_layouts.partials.currency_select')
                    </ul>
                </div>

                <ul class="navbar-nav doctor_nav">
                    <li class="dropdown dropdown-list-toggle">
                        <a target="_blank" href="{{ route('home') }}" class="nav-link nav-link-lg">
                            <i class="fas fa-home"></i> {{ __('Visit Website') }}
                        </a>
                    </li>

                    <li class="dropdown">
                        <a href="javascript:;" data-bs-toggle="dropdown"
                            class="nav-link dropdown-toggle nav-link-lg nav-link-user">
                            @if ($header_clinic->logo)
                                <img alt="logo" src="{{ asset($header_clinic->logo) }}"
                                    class="me-1 rounded-circle" style="width:32px;height:32px;object-fit:cover;">
                            @else
                                <img alt="avatar" src="{{ asset($setting->default_avatar) }}"
                                    class="me-1 rounded-circle">
                            @endif
                            <div class="d-sm-none d-lg-inline-block">{{ $header_clinic->name }}</div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a href="{{ route('clinic.edit-profile') }}"
                                class="dropdown-item has-icon d-flex align-items-center {{ isRoute('clinic.edit-profile', 'text-primary') }}">
                                <i class="far fa-building me-2"></i> {{ __('Clinic Profile') }}
                            </a>
                            <a href="{{ route('clinic.change-password') }}"
                                class="dropdown-item has-icon d-flex align-items-center {{ isRoute('clinic.change-password', 'text-primary') }}">
                                <i class="fas fa-key me-2"></i> {{ __('Change Password') }}
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="javascript:;" class="dropdown-item has-icon d-flex align-items-center"
                                onclick="event.preventDefault(); $('#clinic-logout-form').trigger('submit');">
                                <i class="fas fa-sign-out-alt me-2"></i> {{ __('Logout') }}
                            </a>
                        </div>
                    </li>
                </ul>
            </nav>

            {{-- Sidebar --}}
            @include('clinic.sidebar')

            {{-- Page content --}}
            @yield('clinic-content')

            <footer class="main-footer">
                <div class="footer-right">
                    <p>{{ $contactInfo?->copyright }}</p>
                </div>
            </footer>
        </div>
    </div>

    <form id="clinic-logout-form" action="{{ route('clinic.logout') }}" method="POST" class="d-none">
        @csrf
    </form>

    @include('backend_layouts.partials.javascripts')
    @stack('js')
</body>
</html>
