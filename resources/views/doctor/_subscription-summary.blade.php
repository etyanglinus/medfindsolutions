{{--
    Subscription summary banner — shown at the top of doctor portal pages.
    Displays plan name, expiry, entitlement chips, and a quick inquiries link.
    Requires: $subscription (DoctorSubscription|null)
--}}
@if($subscription?->isActive())
<div class="card mb-4 border-0" style="border-radius:12px;background:linear-gradient(135deg,#eef3ff 0%,#f8faff 100%);box-shadow:0 2px 10px rgba(43,95,222,.08);">
  <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 px-4 py-3">
    <div class="d-flex align-items-center gap-3">
      <div style="width:42px;height:42px;border-radius:10px;background:#2b5fde1a;display:flex;align-items:center;justify-content:center;">
        <i class="fas fa-star text-primary"></i>
      </div>
      <div>
        <div class="fw-bold text-primary" style="font-size:.92rem;">{{ $subscription->package_name }}</div>
        <div class="text-muted" style="font-size:.78rem;">
          {{ __('Valid until') }}: {{ $subscription->expires_at?->format('d M Y') ?? __('No expiry') }}
        </div>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
      @foreach([
        'verified_badge'    => ['fas fa-check-circle text-success', __('Verified')],
        'analytics'         => ['fas fa-chart-line text-warning',   __('Analytics')],
        'whatsapp'          => ['fab fa-whatsapp text-success',      __('WhatsApp')],
        'inquiries'         => ['fas fa-inbox text-info',            __('Inquiries')],
        'homepage_rotation' => ['fas fa-home text-primary',          __('Homepage')],
        'top_specialist_badge' => ['fas fa-trophy text-warning',     __('Top Specialist')],
        'telemedicine'      => ['fas fa-video text-info',            __('Telemedicine')],
        'reviews'           => ['fas fa-star text-warning',          __('Reviews')],
      ] as $key => [$iconClass, $label])
        @if($subscription->hasEntitlement($key))
          <span class="badge rounded-pill px-3 py-2" style="background:#eef3ff;color:#2b5fde;font-size:.75rem;font-weight:500;">
            <i class="{{ $iconClass }} me-1"></i>{{ $label }}
          </span>
        @endif
      @endforeach
      @if($subscription->hasEntitlement('inquiries'))
        <a href="{{ route('doctor.inquiries.index') }}" class="btn btn-sm btn-primary rounded-pill px-3 ms-1">
          <i class="fas fa-inbox me-1"></i>{{ __('Inquiries') }}
        </a>
      @endif
    </div>
  </div>
</div>
@elseif($subscription?->status === 'pending')
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4 rounded-3">
  <i class="fas fa-hourglass-half"></i>
  <span>{{ __('Your') }} <strong>{{ $subscription->package_name }}</strong> {{ __('subscription is awaiting admin approval.') }}</span>
</div>
@else
<div class="alert alert-info d-flex align-items-center justify-content-between gap-2 mb-4 rounded-3">
  <span><i class="fas fa-info-circle me-2"></i>{{ __('No active subscription. Your profile is not visible to the public.') }}</span>
</div>
@endif
