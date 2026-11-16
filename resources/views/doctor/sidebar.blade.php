<div class="main-sidebar">
  <aside id="sidebar-wrapper">
    <div class="sidebar-brand">
      <a href="{{ route('doctor.dashboard') }}">
        <img class="w-75" src="{{ asset($setting->logo) }}" alt="{{ $setting->app_name }}">
      </a>
    </div>
    <div class="sidebar-brand sidebar-brand-sm">
      <a href="{{ route('doctor.dashboard') }}">
        <img src="{{ asset($setting->favicon) }}" alt="{{ $setting->app_name }}">
      </a>
    </div>

    <ul class="sidebar-menu">

      {{-- Dashboard --}}
      <li class="{{ isRoute('doctor.dashboard', 'active') }}">
        <a class="nav-link" href="{{ route('doctor.dashboard') }}">
          <i class="fas fa-home"></i><span>{{ __('Dashboard') }}</span>
        </a>
      </li>

      {{-- ── Profile ─────────────────────────────────── --}}
      <li class="menu-header">{{ __('My Profile') }}</li>

      <li class="{{ isRoute('doctor.edit-profile', 'active') }}">
        <a class="nav-link" href="{{ route('doctor.edit-profile', ['code' => getSessionLanguage()]) }}">
          <i class="fas fa-briefcase-medical"></i><span>{{ __('Manage Portfolio') }}</span>
        </a>
      </li>
      <li class="{{ isRoute('doctor.documents.*', 'active') }}">
        <a class="nav-link" href="{{ route('doctor.documents.index') }}">
          <i class="fas fa-id-card"></i><span>{{ __('License & Documents') }}</span>
        </a>
      </li>
      @if($setting->doctor_can_add_social_links == 'active')
      <li class="{{ isRoute('doctor.social-link.*', 'active') }}">
        <a class="nav-link" href="{{ route('doctor.social-link.index') }}">
          <i class="fas fa-hashtag"></i><span>{{ __('Social Links') }}</span>
        </a>
      </li>
      @endif

      {{-- ── Schedule ────────────────────────────────── --}}
      <li class="menu-header">{{ __('Schedule') }}</li>

      <li class="{{ isRoute('doctor.schedule', 'active') }}">
        <a class="nav-link" href="{{ route('doctor.schedule') }}">
          <i class="fas fa-calendar-alt"></i><span>{{ __('Working Hours') }}</span>
        </a>
      </li>

      {{-- ── Communication ───────────────────────────── --}}
      <li class="menu-header">{{ __('Communication') }}</li>

      @if(app(\Modules\Doctor\app\Services\SubscriptionAccessService::class)->doctorCan(doctorAuth(), 'inquiries'))
      <li class="{{ isRoute('doctor.inquiries.*', 'active') }}">
        <a class="nav-link" href="{{ route('doctor.inquiries.index') }}">
          <i class="fas fa-inbox"></i><span>{{ __('Patient Inquiries') }}</span>
        </a>
      </li>
      @endif
      <li class="{{ isRoute('doctor.admin-messages.*', 'active') }}">
        <a class="nav-link" href="{{ route('doctor.admin-messages.index') }}">
          <i class="fas fa-bell"></i><span>{{ __('Notifications') }}</span>
        </a>
      </li>

      {{-- ── Subscription & Billing ──────────────────── --}}
      <li class="menu-header">{{ __('Subscription') }}</li>

      <li>
        <a class="nav-link" href="{{ route('listing.doctor-plans') }}">
          <i class="fas fa-credit-card"></i><span>{{ __('My Plan / Renew') }}</span>
        </a>
      </li>

      {{-- ── Account ─────────────────────────────────── --}}
      <li class="menu-header">{{ __('Account') }}</li>

      <li class="{{ isRoute('doctor.change-password', 'active') }}">
        <a class="nav-link" href="{{ route('doctor.change-password') }}">
          <i class="fas fa-shield-alt"></i><span>{{ __('Security') }}</span>
        </a>
      </li>
      <li>
        <a class="nav-link" href="javascript:;"
           onclick="event.preventDefault(); document.getElementById('doctor-logout-form').submit();">
          <i class="fas fa-sign-out-alt"></i><span>{{ __('Logout') }}</span>
        </a>
      </li>

    </ul>
  </aside>
</div>
