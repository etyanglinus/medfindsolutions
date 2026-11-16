@extends('clinic.master_layout')
@section('title')
    <title>{{ __('Facility Dashboard') }} — {{ $clinic->name }}</title>
@endsection

@push('css')
<style>
/* ── Portal design system ────────────────────────────── */
.portal-stat-card        { border-radius:14px;border:none;box-shadow:0 2px 12px rgba(0,0,0,.07);transition:transform .18s,box-shadow .18s; }
.portal-stat-card:hover  { transform:translateY(-3px);box-shadow:0 6px 24px rgba(0,0,0,.12); }
.portal-stat-card .icon  { width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0; }
.portal-stat-card .val   { font-size:1.7rem;font-weight:700;line-height:1.1; }
.portal-stat-card .lbl   { font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;font-weight:600; }
.portal-stat-card .sub   { font-size:.78rem;color:#6c757d;margin-top:2px; }

.plan-pill          { display:inline-flex;align-items:center;gap:6px;background:#eef3ff;color:#2b5fde;border-radius:100px;padding:4px 14px;font-size:.82rem;font-weight:600; }
.plan-pill.no-plan  { background:#f8f9fa;color:#6c757d; }
.plan-pill.pending  { background:#fff8e1;color:#92400e; }
.plan-pill.rejected { background:#fef2f2;color:#b91c1c; }

.quick-action       { border-radius:12px;border:1.5px solid #e9ecef;background:#fff;padding:18px 12px;text-align:center;transition:all .18s;cursor:pointer;text-decoration:none;color:#343a40;display:block; }
.quick-action:hover { border-color:#2b5fde;background:#f0f4ff;color:#2b5fde;text-decoration:none;transform:translateY(-2px); }
.quick-action i     { font-size:1.4rem;display:block;margin-bottom:6px; }
.quick-action .qa-label { font-size:.82rem;font-weight:600; }

.entitlement-chip        { display:inline-flex;align-items:center;gap:5px;background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;border-radius:100px;padding:3px 11px;font-size:.78rem;font-weight:500; }
.entitlement-chip.locked { background:#f8f9fa;color:#adb5bd;border-color:#dee2e6; }

.chart-card           { border-radius:14px;border:none;box-shadow:0 2px 12px rgba(0,0,0,.07); }
.chart-card .card-header { background:#fff;border-bottom:1px solid #f1f3f5;border-radius:14px 14px 0 0;padding:18px 22px 14px; }

.profile-info-label { font-size:.78rem;text-transform:uppercase;letter-spacing:.05em;color:#6c757d;font-weight:600; }
.profile-info-val   { font-size:.92rem;color:#212529; }

.sub-expiry-bar      { height:6px;border-radius:3px;background:#e9ecef;overflow:hidden; }
.sub-expiry-bar .fill{ height:100%;border-radius:3px;transition:width .6s ease; }

.portal-section-title { font-size:1rem;font-weight:700;letter-spacing:.01em;margin-bottom:1rem; }

/* Status badges */
.status-approved { background:#d1fae5;color:#065f46;border-radius:100px;padding:3px 12px;font-size:.76rem;font-weight:600; }
.status-pending  { background:#fef3c7;color:#92400e;border-radius:100px;padding:3px 12px;font-size:.76rem;font-weight:600; }
.status-rejected { background:#fee2e2;color:#b91c1c;border-radius:100px;padding:3px 12px;font-size:.76rem;font-weight:600; }
.status-none     { background:#f3f4f6;color:#6b7280;border-radius:100px;padding:3px 12px;font-size:.76rem;font-weight:600; }
</style>
@endpush

@section('clinic-content')
<div class="main-content">
  <section class="section">
    <x-admin.breadcrumb title="{{ __('Dashboard') }}" />

    <div class="section-body px-1">
      {{-- ═══════════════════════════════════════════════════════
           ROW 1 — Welcome + plan badge
      ═══════════════════════════════════════════════════════ --}}
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
          <h4 class="fw-bold mb-0">{{ $clinic->name }}</h4>
          <span class="text-muted small">{{ now()->format('l, d F Y') }}</span>
        </div>
        <div class="d-flex align-items-center gap-2">
          @if($subscription?->isActive())
            <span class="plan-pill">
              <i class="fas fa-check-circle"></i>
              {{ $subscription->package_name }}
              @if($subscription->expires_at)
                &mdash; {{ __('expires') }} {{ $subscription->expires_at->format('d M Y') }}
              @endif
            </span>
          @elseif($subscription?->status === 'pending')
            <span class="plan-pill pending">
              <i class="fas fa-hourglass-half"></i> {{ __('Awaiting Approval') }}
            </span>
          @elseif($subscription?->status === 'rejected')
            <span class="plan-pill rejected">
              <i class="fas fa-times-circle"></i> {{ __('Payment Rejected') }}
            </span>
          @else
            <span class="plan-pill no-plan">
              <i class="fas fa-exclamation-circle"></i> {{ __('No Active Plan') }}
            </span>
          @endif
          <a href="{{ route('clinic.subscription.status') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
            <i class="fas fa-sync-alt me-1"></i>{{ __('Renew') }}
          </a>
        </div>
      </div>

      {{-- State alerts --}}
      @if($subscription?->status === 'pending')
        <div class="alert alert-warning d-flex align-items-center gap-2 rounded-3 mb-3">
          <i class="fas fa-hourglass-half fa-lg"></i>
          <span>{{ __('Your') }} <strong>{{ $subscription->package_name }}</strong> {{ __('payment is awaiting admin approval. Your facility listing will go live once confirmed.') }}</span>
        </div>
      @elseif($subscription?->status === 'rejected')
        <div class="alert alert-danger d-flex align-items-center gap-2 rounded-3 mb-3">
          <i class="fas fa-times-circle fa-lg"></i>
          <span>{{ __('Your last payment was rejected.') }}@if($subscription->admin_note) — {{ $subscription->admin_note }}@endif
            <a href="{{ route('clinic.subscription.status') }}" class="fw-bold ms-2">{{ __('Submit again') }}</a>
          </span>
        </div>
      @elseif(! $subscription)
        <div class="alert alert-info d-flex align-items-center gap-2 rounded-3 mb-3">
          <i class="fas fa-info-circle fa-lg"></i>
          <span>{{ __('You have no active subscription. Your facility is not publicly visible.') }}</span>
        </div>
      @endif

      {{-- Listing performance, below the welcome header and plan status --}}
      @include('provider.partials.listing-insights', ['insightType' => 'clinic'])

      {{-- ═══════════════════════════════════════════════════════
           ROW 2 — Stat cards
      ═══════════════════════════════════════════════════════ --}}
      <div class="row g-3 mb-4">

        {{-- Profile views --}}
        <div class="col-6 col-md-3">
          <div class="card portal-stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-eye"></i></div>
              <div>
                <div class="val">{{ number_format($profileViewsTotal) }}</div>
                <div class="lbl">{{ __('Profile Views') }}</div>
                <div class="sub">{{ $profileViews30Days }} {{ __('last 30 days') }}</div>
              </div>
            </div>
          </div>
        </div>

        {{-- Plan --}}
        <div class="col-6 col-md-3">
          <div class="card portal-stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-star"></i></div>
              <div>
                <div class="val" style="font-size:1.1rem;">{{ $subscription?->package_name ?? '—' }}</div>
                <div class="lbl">{{ __('Active Plan') }}</div>
              </div>
            </div>
          </div>
        </div>

        {{-- Plan duration --}}
        <div class="col-6 col-md-3">
          <div class="card portal-stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="icon bg-info bg-opacity-10 text-info"><i class="fas fa-calendar-alt"></i></div>
              <div>
                <div class="val" style="font-size:1.1rem;">
                  {{ $subscription ? $subscription->plan_duration . ' ' . __('mo') : '—' }}
                </div>
                <div class="lbl">{{ __('Duration') }}</div>
                @if($subscription?->expires_at)
                  <div class="sub">{{ __('Until') }} {{ $subscription->expires_at->format('d M Y') }}</div>
                @endif
              </div>
            </div>
          </div>
        </div>

        {{-- Subscription status --}}
        <div class="col-6 col-md-3">
          <div class="card portal-stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="icon {{ $subscription?->status === 'approved' ? 'bg-success bg-opacity-10 text-success' : ($subscription?->status === 'rejected' ? 'bg-danger bg-opacity-10 text-danger' : 'bg-warning bg-opacity-10 text-warning') }}">
                <i class="fas fa-shield-alt"></i>
              </div>
              <div>
                <div class="lbl">{{ __('Listing Status') }}</div>
                <div class="mt-1">
                  @if($subscription?->status === 'approved')
                    <span class="status-approved"><i class="fas fa-circle me-1" style="font-size:.5rem;"></i>{{ __('Live') }}</span>
                  @elseif($subscription?->status === 'pending')
                    <span class="status-pending"><i class="fas fa-circle me-1" style="font-size:.5rem;"></i>{{ __('Pending') }}</span>
                  @elseif($subscription?->status === 'rejected')
                    <span class="status-rejected"><i class="fas fa-circle me-1" style="font-size:.5rem;"></i>{{ __('Rejected') }}</span>
                  @else
                    <span class="status-none"><i class="fas fa-circle me-1" style="font-size:.5rem;"></i>{{ __('Inactive') }}</span>
                  @endif
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>{{-- /stat row --}}

      {{-- ═══════════════════════════════════════════════════════
           ROW 3 — Quick actions
      ═══════════════════════════════════════════════════════ --}}
      <div class="card mb-4" style="border-radius:14px;border:none;box-shadow:0 2px 12px rgba(0,0,0,.07);">
        <div class="card-body p-4">
          <p class="portal-section-title mb-3">
            <i class="fas fa-bolt text-warning me-2"></i>{{ __('Quick Actions') }}
          </p>
          <div class="row g-3">
            <div class="col-6 col-sm-4 col-lg-2">
              <a href="{{ route('clinic.edit-profile') }}" class="quick-action">
                <i class="fas fa-building text-primary"></i>
                <span class="qa-label">{{ __('Edit Profile') }}</span>
              </a>
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
              <a href="{{ route('clinic.content.index') }}" class="quick-action">
                <i class="fas fa-images text-success"></i>
                <span class="qa-label">{{ __('Gallery & Branches') }}</span>
              </a>
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
              <a href="{{ route('clinic.inquiries.index') }}" class="quick-action">
                <i class="fas fa-inbox text-warning"></i>
                <span class="qa-label">{{ __('Patient Inquiries') }}</span>
              </a>
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
              <a href="{{ route('clinic.documents.index') }}" class="quick-action">
                <i class="fas fa-id-card" style="color:#7c3aed;"></i>
                <span class="qa-label">{{ __('License Docs') }}</span>
              </a>
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
              <a href="{{ route('clinic.admin-messages.index') }}" class="quick-action">
                <i class="fas fa-bell text-danger"></i>
                <span class="qa-label">{{ __('Notifications') }}</span>
              </a>
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
              <a href="{{ route('clinic.change-password') }}" class="quick-action">
                <i class="fas fa-shield-alt text-info"></i>
                <span class="qa-label">{{ __('Security') }}</span>
              </a>
            </div>
          </div>
        </div>
      </div>

      {{-- ═══════════════════════════════════════════════════════
           ROW 4 — Plan entitlements + Profile analytics side-by-side
      ═══════════════════════════════════════════════════════ --}}
      <div class="row g-3 mb-4">

        {{-- Plan features --}}
        <div class="col-lg-5">
          <div class="card h-100" style="border-radius:14px;border:none;box-shadow:0 2px 12px rgba(0,0,0,.07);">
            <div class="card-body p-4">
              <p class="portal-section-title">
                <i class="fas fa-star text-warning me-2"></i>{{ __('Your Plan Features') }}
              </p>
              @if($subscription?->isActive())
                <p class="text-muted small mb-3">
                  <strong>{{ $subscription->package_name }}</strong>
                  @if($subscription->expires_at && $subscription->approved_at)
                    @php
                      $totalDays = $subscription->approved_at->diffInDays($subscription->expires_at);
                      $daysLeft  = max(0, now()->diffInDays($subscription->expires_at, false));
                      $pct       = $totalDays > 0 ? min(100, round(($daysLeft / $totalDays) * 100)) : 100;
                      $barColor  = $pct > 30 ? '#22c55e' : ($pct > 10 ? '#f59e0b' : '#ef4444');
                    @endphp
                    &mdash; {{ $daysLeft }} {{ __('day(s) remaining') }}
                    <div class="sub-expiry-bar mt-2 mb-3">
                      <div class="fill" style="width:{{ $pct }}%;background:{{ $barColor }};"></div>
                    </div>
                  @endif
                </p>
                <div class="d-flex flex-wrap gap-2">
                  @foreach([
                    'contact'          => ['fas fa-phone-alt',          __('Contact Info')],
                    'whatsapp'         => ['fab fa-whatsapp',            __('WhatsApp')],
                    'description'      => ['fas fa-align-left',          __('Description')],
                    'insurance'        => ['fas fa-shield-alt',          __('Insurance')],
                    'maps'             => ['fas fa-map-marker-alt',      __('Google Maps')],
                    'cover_photo'      => ['fas fa-camera',              __('Cover Photo')],
                    'gallery_limit'    => ['fas fa-images',              __('Gallery')],
                    'equipment'        => ['fas fa-tools',               __('Equipment')],
                    'reviews'          => ['fas fa-star',                __('Reviews')],
                    'emergency'        => ['fas fa-ambulance',           __('Emergency')],
                    'operating_hours'  => ['fas fa-clock',               __('Operating Hours')],
                    'branches'         => ['fas fa-code-branch',         __('Branches')],
                    'featured_services'=> ['fas fa-list-ul',             __('Featured Services')],
                    'inquiries'        => ['fas fa-inbox',               __('Inquiries')],
                    'analytics'        => ['fas fa-chart-line',          __('Analytics')],
                    'top_clinic_badge' => ['fas fa-trophy',              __('Top Clinic Badge')],
                    'homepage_rotation'=> ['fas fa-home',                __('Homepage Placement')],
                  ] as $key => [$icon, $label])
                    <span class="entitlement-chip {{ $subscription->hasEntitlement($key) ? '' : 'locked' }}">
                      <i class="{{ $icon }} fa-xs"></i> {{ $label }}
                    </span>
                  @endforeach
                </div>
              @else
                <div class="text-center py-4">
                  <i class="fas fa-lock fa-2x text-muted mb-3"></i>
                  <p class="text-muted small">{{ __('Activate a subscription to unlock facility features.') }}</p>
                  <a href="{{ route('clinic.subscription.status') }}" class="btn btn-sm btn-primary rounded-pill px-4">
                    {{ __('View Plans') }}
                  </a>
                </div>
              @endif
            </div>
          </div>
        </div>

        {{-- Profile analytics --}}
        <div class="col-lg-7">
          <div class="card chart-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
              <div>
                <span class="fw-bold">{{ __('Profile Views') }}</span>
                <span class="badge bg-warning bg-opacity-15 text-warning ms-2">{{ __('Last 30 days') }}</span>
              </div>
              <span class="text-muted small">{{ number_format($profileViewsTotal) }} {{ __('total') }}</span>
            </div>
            <div class="card-body" style="height:240px;">
              <canvas id="clinicProfileViewsChart"></canvas>
            </div>
          </div>
        </div>

      </div>{{-- /plan + analytics --}}

      {{-- ═══════════════════════════════════════════════════════
           ROW 5 — Facility profile snapshot
      ═══════════════════════════════════════════════════════ --}}
      <div class="card mb-4" style="border-radius:14px;border:none;box-shadow:0 2px 12px rgba(0,0,0,.07);">
        <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-radius:14px 14px 0 0;border-bottom:1px solid #f1f3f5;padding:18px 22px 14px;">
          <span class="fw-bold"><i class="fas fa-building text-primary me-2"></i>{{ __('Facility Overview') }}</span>
          <a href="{{ route('clinic.edit-profile') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
            <i class="fas fa-edit me-1"></i>{{ __('Edit Profile') }}
          </a>
        </div>
        <div class="card-body p-4">
          <div class="row align-items-start">

            {{-- Logo --}}
            <div class="col-md-2 text-center mb-4 mb-md-0">
              @if($clinic->logo)
                <img src="{{ asset($clinic->logo) }}" alt="{{ $clinic->name }}"
                  class="rounded-3 border" style="width:100px;height:100px;object-fit:cover;box-shadow:0 2px 8px rgba(0,0,0,.1);">
              @else
                <div class="rounded-3 bg-light border d-flex align-items-center justify-content-center mx-auto"
                  style="width:100px;height:100px;">
                  <i class="fas fa-hospital fa-2x text-muted"></i>
                </div>
              @endif
              <div class="mt-2">
                @if($subscription?->hasEntitlement('top_clinic_badge'))
                  <span class="badge bg-warning text-dark"><i class="fas fa-trophy me-1"></i>{{ __('Top Clinic') }}</span>
                @endif
              </div>
            </div>

            {{-- Info grid --}}
            <div class="col-md-10">
              <div class="row g-3">

                <div class="col-md-4">
                  <div class="profile-info-label">{{ __('Facility Name') }}</div>
                  <div class="profile-info-val fw-semibold">{{ $clinic->name }}</div>
                </div>
                <div class="col-md-4">
                  <div class="profile-info-label">{{ __('Specialty') }}</div>
                  <div class="profile-info-val">{{ $clinic->specialty ?? '—' }}</div>
                </div>
                <div class="col-md-4">
                  <div class="profile-info-label">{{ __('Phone') }}</div>
                  <div class="profile-info-val">{{ $clinic->phone ?? '—' }}</div>
                </div>
                <div class="col-md-4">
                  <div class="profile-info-label">{{ __('Email') }}</div>
                  <div class="profile-info-val">{{ $clinic->email }}</div>
                </div>
                @if($subscription?->hasEntitlement('whatsapp'))
                <div class="col-md-4">
                  <div class="profile-info-label">{{ __('WhatsApp') }}</div>
                  <div class="profile-info-val">{{ $clinic->whatsapp ?? '—' }}</div>
                </div>
                @endif
                @if($subscription?->hasEntitlement('operating_hours'))
                <div class="col-md-4">
                  <div class="profile-info-label">{{ __('Operating Hours') }}</div>
                  <div class="profile-info-val">{{ $clinic->operating_hours ?? '—' }}</div>
                </div>
                @endif
                @if($subscription?->hasEntitlement('insurance'))
                <div class="col-md-6">
                  <div class="profile-info-label">{{ __('Insurance Accepted') }}</div>
                  <div class="profile-info-val">{{ $clinic->insurance_accepted ?? '—' }}</div>
                </div>
                @endif
                @if($subscription?->hasEntitlement('emergency'))
                <div class="col-md-3">
                  <div class="profile-info-label">{{ __('Emergency') }}</div>
                  <div class="profile-info-val">
                    @if($clinic->emergency_available)
                      <span class="status-approved"><i class="fas fa-ambulance me-1"></i>{{ __('Available') }}</span>
                    @else
                      <span class="status-none">{{ __('Not Available') }}</span>
                    @endif
                  </div>
                </div>
                @endif
                @if($subscription?->hasEntitlement('description') && $clinic->description)
                <div class="col-12">
                  <div class="profile-info-label">{{ __('About') }}</div>
                  <div class="profile-info-val text-muted small" style="max-height:60px;overflow:hidden;">
                    {{ Str::limit($clinic->description, 200) }}
                  </div>
                </div>
                @endif

              </div>{{-- /row g-3 --}}

              {{-- Services tags --}}
              @if($subscription?->hasEntitlement('services_limit') && $clinic->relationLoaded('services') && $clinic->services->count())
                <div class="mt-3">
                  <div class="profile-info-label mb-2">{{ __('Services') }}</div>
                  @foreach($clinic->services->take(8) as $svc)
                    <span class="badge bg-light text-dark border me-1 mb-1" style="font-size:.78rem;">{{ $svc->name ?? $svc->title ?? '' }}</span>
                  @endforeach
                  @if($clinic->services->count() > 8)
                    <span class="text-muted small">+{{ $clinic->services->count() - 8 }} {{ __('more') }}</span>
                  @endif
                </div>
              @endif

            </div>
          </div>{{-- /row --}}
        </div>
      </div>{{-- /facility overview --}}

    </div>{{-- /section-body --}}
  </section>
</div>
@endsection

@push('js')
<script src="{{ asset('backend/chart.js/chart.umd.min.js') }}"></script>
<script>
(function () {
  "use strict";
  new Chart(document.getElementById('clinicProfileViewsChart'), {
    type: 'bar',
    data: {
      labels: @json($profileViewLabels),
      datasets: [{
        label: @json(__('Profile views')),
        data:   @json($profileViewData),
        backgroundColor: 'rgba(36,103,168,.65)',
        borderRadius: 4,
        hoverBackgroundColor: '#2467a8',
      }]
    },
    options: {
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 } } },
        y: { beginAtZero: true, ticks: { precision: 0 } }
      }
    }
  });
})();
</script>
@endpush
