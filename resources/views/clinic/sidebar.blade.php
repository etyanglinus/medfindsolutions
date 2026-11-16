<div class="main-sidebar">
  <aside id="sidebar-wrapper">
    <div class="sidebar-brand">
      <a href="{{ route('clinic.dashboard') }}">
        <img class="w-75" src="{{ asset($setting->logo) }}" alt="{{ $setting->app_name }}">
      </a>
    </div>
    <div class="sidebar-brand sidebar-brand-sm">
      <a href="{{ route('clinic.dashboard') }}">
        <img src="{{ asset($setting->favicon) }}" alt="{{ $setting->app_name }}">
      </a>
    </div>

    <ul class="sidebar-menu">

      {{-- Dashboard --}}
      <li class="{{ isRoute('clinic.dashboard', 'active') }}">
        <a class="nav-link" href="{{ route('clinic.dashboard') }}">
          <i class="fas fa-home"></i><span>{{ __('Dashboard') }}</span>
        </a>
      </li>

      {{-- ── Facility Profile ─────────────────────────── --}}
      <li class="menu-header">{{ __('Facility Profile') }}</li>

      <li class="{{ isRoute('clinic.edit-profile', 'active') }}">
        <a class="nav-link" href="{{ route('clinic.edit-profile') }}">
          <i class="fas fa-briefcase-medical"></i><span>{{ __('Manage Portfolio') }}</span>
        </a>
      </li>
      <li class="{{ isRoute('clinic.content.*', 'active') }}">
        <a class="nav-link" href="{{ route('clinic.content.index') }}">
          <i class="fas fa-images"></i><span>{{ __('Gallery & Branches') }}</span>
        </a>
      </li>
      <li class="{{ isRoute('clinic.documents.*', 'active') }}">
        <a class="nav-link" href="{{ route('clinic.documents.index') }}">
          <i class="fas fa-id-card"></i><span>{{ __('License & Documents') }}</span>
        </a>
      </li>
      <li class="{{ request()->routeIs('listing.receipts*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('listing.receipts', ['type' => 'clinic']) }}">
          <i class="fas fa-file-invoice"></i><span>{{ __('Payment receipts') }}</span>
        </a>
      </li>

      {{-- ── Communication ───────────────────────────── --}}
      <li class="menu-header">{{ __('Communication') }}</li>

      <li class="{{ isRoute('clinic.inquiries.*', 'active') }}">
        <a class="nav-link" href="{{ route('clinic.inquiries.index') }}">
          <i class="fas fa-inbox"></i><span>{{ __('Patient Inquiries') }}</span>
        </a>
      </li>
      <li class="{{ isRoute('clinic.admin-messages.*', 'active') }}">
        <a class="nav-link" href="{{ route('clinic.admin-messages.index') }}">
          <i class="fas fa-bell"></i><span>{{ __('Notifications') }}</span>
        </a>
      </li>

      {{-- ── Subscription ────────────────────────────── --}}
      <li class="menu-header">{{ __('Subscription') }}</li>

      <li class="{{ isRoute('clinic.subscription.*', 'active') }}">
        <a class="nav-link" href="{{ route('clinic.subscription.status') }}">
          <i class="fas fa-layer-group"></i><span>{{ __('My Plan') }}</span>
        </a>
      </li>

      {{-- ── Account ─────────────────────────────────── --}}
      <li class="menu-header">{{ __('Account') }}</li>

      <li class="{{ isRoute('clinic.change-password', 'active') }}">
        <a class="nav-link" href="{{ route('clinic.change-password') }}">
          <i class="fas fa-shield-alt"></i><span>{{ __('Security') }}</span>
        </a>
      </li>
      <li>
        <a class="nav-link" href="javascript:;"
           onclick="event.preventDefault(); document.getElementById('clinic-logout-form').submit();">
          <i class="fas fa-sign-out-alt"></i><span>{{ __('Logout') }}</span>
        </a>
      </li>

    </ul>
  </aside>
</div>
