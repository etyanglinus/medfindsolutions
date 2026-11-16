@extends('doctor.master_layout')
@section('title')
    <title>{{ __('Doctor Dashboard') }} — {{ $header_doctor->name ?? '' }}</title>
@endsection

@push('css')
<style>
/* ── Portal layout overrides ─────────────────────────── */
.portal-stat-card        { border-radius:14px; border:none; box-shadow:0 2px 12px rgba(0,0,0,.07); transition:transform .18s,box-shadow .18s; }
.portal-stat-card:hover  { transform:translateY(-3px); box-shadow:0 6px 24px rgba(0,0,0,.12); }
.portal-stat-card .icon  { width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0; }
.portal-stat-card .val   { font-size:1.7rem;font-weight:700;line-height:1.1; }
.portal-stat-card .lbl   { font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;font-weight:600; }
.portal-stat-card .sub   { font-size:.78rem;color:#6c757d;margin-top:2px; }

.plan-pill { display:inline-flex;align-items:center;gap:6px;background:#eef3ff;color:#2b5fde;border-radius:100px;padding:4px 14px;font-size:.82rem;font-weight:600; }
.plan-pill.no-plan { background:#f8f9fa;color:#6c757d; }

.portal-section-title { font-size:1rem;font-weight:700;letter-spacing:.01em;margin-bottom:1rem; }

.quick-action { border-radius:12px;border:1.5px solid #e9ecef;background:#fff;padding:18px 12px;text-align:center;transition:all .18s;cursor:pointer;text-decoration:none;color:#343a40;display:block; }
.quick-action:hover { border-color:#2b5fde;background:#f0f4ff;color:#2b5fde;text-decoration:none;transform:translateY(-2px); }
.quick-action i { font-size:1.4rem;display:block;margin-bottom:6px; }
.quick-action .qa-label { font-size:.82rem;font-weight:600; }

.entitlement-chip { display:inline-flex;align-items:center;gap:5px;background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;border-radius:100px;padding:3px 11px;font-size:.78rem;font-weight:500; }
.entitlement-chip.locked { background:#f8f9fa;color:#adb5bd;border-color:#dee2e6; }

.chart-card { border-radius:14px;border:none;box-shadow:0 2px 12px rgba(0,0,0,.07); }
.chart-card .card-header { background:#fff;border-bottom:1px solid #f1f3f5;border-radius:14px 14px 0 0;padding:18px 22px 14px; }

.today-table thead th { background:#f8f9fa;font-size:.8rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;border-bottom:2px solid #e9ecef; }
.today-table tbody td { font-size:.88rem;vertical-align:middle; }

.status-badge-pending  { background:#fff3cd;color:#856404;border-radius:100px;padding:2px 10px;font-size:.75rem;font-weight:600; }
.status-badge-success  { background:#d1fae5;color:#065f46;border-radius:100px;padding:2px 10px;font-size:.75rem;font-weight:600; }

.sub-expiry-bar { height:6px;border-radius:3px;background:#e9ecef;overflow:hidden; }
.sub-expiry-bar .fill { height:100%;border-radius:3px;background:linear-gradient(90deg,#22c55e,#16a34a); transition:width .6s ease; }

.section-divider { border:none;border-top:1px solid #f1f3f5;margin:1.5rem 0; }
</style>
@endpush

@section('admin-content')
<div class="main-content">
  <section class="section">
    <x-admin.breadcrumb title="{{ __('Dashboard') }}" />

    <div class="section-body px-1">
        @include('provider.partials.listing-insights', ['insightType' => 'doctor'])

      {{-- ═══════════════════════════════════════════════════════
           ROW 1 — Welcome + Plan badge
      ═══════════════════════════════════════════════════════ --}}
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
          <h4 class="fw-bold mb-0">{{ __('Welcome back') }}, {{ $header_doctor->name ?? __('Doctor') }}</h4>
          <span class="text-muted small">{{ now()->format('l, d F Y') }}</span>
        </div>
        <div>
          @if($subscription?->isActive())
            <span class="plan-pill">
              <i class="fas fa-check-circle"></i>
              {{ $subscription->package_name }} &mdash;
              {{ __('expires') }} {{ $subscription->expires_at?->format('d M Y') ?? __('No expiry') }}
            </span>
          @elseif($subscription)
            <span class="plan-pill no-plan"><i class="fas fa-hourglass-half"></i> {{ __(ucfirst($subscription->status)) }}</span>
          @else
            <span class="plan-pill no-plan"><i class="fas fa-exclamation-circle"></i> {{ __('No active plan') }}</span>
          @endif
          <a href="{{ route('listing.doctor-plans') }}" class="btn btn-sm btn-primary rounded-pill ms-2">
            <i class="fas fa-sync-alt me-1"></i>{{ $subscription?->isActive() ? __('Renew / Upgrade') : __('Choose a plan & pay') }}
          </a>
        </div>
      </div>

      {{-- Subscription state alerts --}}
      @if($subscription?->status === 'pending')
        <div class="alert alert-warning d-flex align-items-center gap-2 rounded-3 mb-3">
          <i class="fas fa-hourglass-half fa-lg"></i>
          <span>{{ __('Your') }} <strong>{{ $subscription->package_name }}</strong> {{ __('payment is awaiting admin approval. Your profile will go live once confirmed.') }}</span>
        </div>
      @elseif($subscription?->status === 'rejected')
        <div class="alert alert-danger d-flex align-items-center gap-2 rounded-3 mb-3">
          <i class="fas fa-times-circle fa-lg"></i>
          <span>{{ __('Your last payment was rejected.') }}@if($subscription->admin_note) — {{ $subscription->admin_note }}@endif</span>
        </div>
      @elseif(! $subscription)
        <div class="alert alert-info d-flex align-items-center gap-2 rounded-3 mb-3">
          <i class="fas fa-info-circle fa-lg"></i>
          <span>{{ __('You have no active subscription. Your profile is not publicly visible.') }}</span>
        </div>
      @endif

      {{-- ═══════════════════════════════════════════════════════
           ROW 2 — Stat cards
      ═══════════════════════════════════════════════════════ --}}
      <div class="row g-3 mb-4">

        {{-- Profile Views --}}
        @if($subscription?->hasEntitlement('analytics'))
        <div class="col-6 col-md-3">
          <div class="card portal-stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-eye"></i></div>
              <div>
                <div class="val">{{ number_format($profile_views_total) }}</div>
                <div class="lbl">{{ __('Profile Views') }}</div>
                <div class="sub">{{ $profile_views_30_days }} {{ __('last 30 days') }}</div>
              </div>
            </div>
          </div>
        </div>
        @endif


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
              <a href="{{ route('doctor.edit-profile', ['code' => getSessionLanguage()]) }}" class="quick-action">
                <i class="fas fa-user-md text-primary"></i>
                <span class="qa-label">{{ __('Edit Profile') }}</span>
              </a>
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
              <a href="{{ route('doctor.schedule') }}" class="quick-action">
                <i class="fas fa-clock text-success"></i>
                <span class="qa-label">{{ __('Working Hours') }}</span>
              </a>
            </div>
            @if($subscription?->hasEntitlement('inquiries'))
            <div class="col-6 col-sm-4 col-lg-2">
              <a href="{{ route('doctor.inquiries.index') }}" class="quick-action">
                <i class="fas fa-inbox text-warning"></i>
                <span class="qa-label">{{ __('Inquiries') }}</span>
              </a>
            </div>
            @endif
            <div class="col-6 col-sm-4 col-lg-2">
              <a href="{{ route('doctor.admin-messages.index') }}" class="quick-action">
                <i class="fas fa-bell text-danger"></i>
                <span class="qa-label">{{ __('Notifications') }}</span>
              </a>
            </div>
            <div class="col-6 col-sm-4 col-lg-2">
              <a href="{{ route('doctor.documents.index') }}" class="quick-action">
                <i class="fas fa-id-card" style="color:#7c3aed;"></i>
                <span class="qa-label">{{ __('License Docs') }}</span>
              </a>
            </div>
          </div>
        </div>
      </div>

      {{-- ═══════════════════════════════════════════════════════
           ROW 4 — Plan entitlements + Profile analytics side-by-side
      ═══════════════════════════════════════════════════════ --}}
      <div class="row g-3 mb-4">

        {{-- Plan features card --}}
        <div class="col-lg-5">
          <div class="card h-100" style="border-radius:14px;border:none;box-shadow:0 2px 12px rgba(0,0,0,.07);">
            <div class="card-body p-4">
              <p class="portal-section-title">
                <i class="fas fa-star text-warning me-2"></i>{{ __('Your Plan Features') }}
              </p>
              @if($subscription?->isActive())
                <p class="text-muted small mb-3">
                  <strong>{{ $subscription->package_name }}</strong> &mdash;
                  {{ __('valid until') }} {{ $subscription->expires_at?->format('d M Y') ?? __('no expiry') }}
                </p>

                {{-- Expiry progress bar --}}
                @if($subscription->expires_at && $subscription->approved_at)
                  @php
                    $totalDays  = $subscription->approved_at->diffInDays($subscription->expires_at);
                    $daysLeft   = max(0, now()->diffInDays($subscription->expires_at, false));
                    $pct        = $totalDays > 0 ? min(100, round(($daysLeft / $totalDays) * 100)) : 100;
                    $barColor   = $pct > 30 ? '#22c55e' : ($pct > 10 ? '#f59e0b' : '#ef4444');
                  @endphp
                  <div class="sub-expiry-bar mb-3">
                    <div class="fill" style="width:{{ $pct }}%;background:{{ $barColor }};"></div>
                  </div>
                  <p class="small text-muted mb-3">{{ $daysLeft }} {{ __('day(s) remaining') }}</p>
                @endif

                <div class="d-flex flex-wrap gap-2">
                  @foreach([
                    'contact'            => ['fas fa-phone-alt',       __('Contact')],
                    'whatsapp'           => ['fab fa-whatsapp',         __('WhatsApp')],
                    'verified_badge'     => ['fas fa-check-circle',     __('Verified Badge')],
                    'consultation_fee'   => ['fas fa-stethoscope',      __('Consultation Fee')],
                    'services'           => ['fas fa-briefcase-medical',__('Services')],
                    'analytics'          => ['fas fa-chart-line',       __('Analytics')],
                    'insurance'          => ['fas fa-shield-alt',       __('Insurance')],
                    'telemedicine'       => ['fas fa-video',             __('Telemedicine')],
                    'emergency'          => ['fas fa-ambulance',        __('Emergency')],
                    'reviews'            => ['fas fa-star',             __('Reviews')],
                    'inquiries'          => ['fas fa-inbox',            __('Inquiries')],
                    'homepage_rotation'  => ['fas fa-home',             __('Homepage Placement')],
                    'top_specialist_badge'=>['fas fa-trophy',           __('Top Specialist')],
                  ] as $key => [$icon, $label])
                    <span class="entitlement-chip {{ $subscription->hasEntitlement($key) ? '' : 'locked' }}">
                      <i class="{{ $icon }} fa-xs"></i> {{ $label }}
                    </span>
                  @endforeach
                </div>
              @else
                <div class="text-center py-4">
                  <i class="fas fa-lock fa-2x text-muted mb-3"></i>
                  <p class="text-muted small">{{ __('Activate a subscription to unlock platform features.') }}</p>
                </div>
              @endif
            </div>
          </div>
        </div>

        {{-- Profile analytics chart --}}
        @if($subscription?->hasEntitlement('analytics'))
        <div class="col-lg-7">
          <div class="card chart-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
              <div>
                <span class="fw-bold">{{ __('Profile Views') }}</span>
                <span class="badge bg-warning bg-opacity-15 text-warning ms-2">{{ __('Last 30 days') }}</span>
              </div>
              <span class="text-muted small">{{ number_format($profile_views_total) }} {{ __('total') }}</span>
            </div>
            <div class="card-body" style="height:240px;">
              <canvas id="profileViewsChart"></canvas>
            </div>
          </div>
        </div>
        @else
        <div class="col-lg-7">
          <div class="card chart-card h-100 d-flex align-items-center justify-content-center">
            <div class="text-center py-5">
              <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
              <p class="fw-semibold text-muted">{{ __('Analytics') }}</p>
              <p class="small text-muted">{{ __('Upgrade to Premium or Executive to unlock profile view analytics.') }}</p>
            </div>
          </div>
        </div>
        @endif

      </div>{{-- /plan + analytics --}}

    </div>{{-- /section-body --}}
  </section>
</div>
@endsection

@push('js')
<script src="{{ asset('backend/chart.js/chart.umd.min.js') }}"></script>
<script>
(function () {
  "use strict";

  /* ── Profile views chart ───────────────────────────── */
  var pvChart = document.getElementById('profileViewsChart');
  if (pvChart) {
    new Chart(pvChart, {
      type: 'bar',
      data: {
        labels: @json($profile_view_labels),
        datasets: [{
          label: @json(__('Views')),
          data: @json($profile_view_data),
          backgroundColor: 'rgba(245,158,11,.75)',
          borderRadius: 4,
          hoverBackgroundColor: '#f59e0b',
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
  }
})();
</script>
@endpush
