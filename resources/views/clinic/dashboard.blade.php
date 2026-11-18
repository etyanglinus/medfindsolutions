@extends('clinic.master_layout')
@section('title')
    <title>{{ __('Facility Dashboard') }} — {{ $clinic->name }}</title>
@endsection

@php
    $logo = $clinic->logo ? asset($clinic->logo) : null;
    $planName = $subscription?->package_name;
    $expiring = $daysLeft !== null && $daysLeft <= 30;
    $usedPct = $planDays && $daysLeft !== null ? min(100, max(4, (int) round(100 * $daysLeft / $planDays))) : null;
    $benefitLabels = [
        'search_visibility' => __('Listed in search'), 'contact' => __('Contact details'), 'whatsapp' => __('WhatsApp button'), 'description' => __('Full description'),
        'insurance' => __('Insurance list'), 'maps' => __('Google Maps'), 'top_clinic_badge' => __('Top facility badge'), 'homepage_rotation' => __('Home page spot'),
        'cover_photo' => __('Cover photo'), 'equipment' => __('Equipment'), 'reviews' => __('Patient reviews'), 'emergency' => __('Emergency tag'),
        'operating_hours' => __('Opening hours'), 'branches' => __('Branches'), 'featured_services' => __('Featured services'), 'inquiries' => __('Enquiries inbox'),
        'analytics' => __('Analytics'), 'services_limit' => __('Services'), 'gallery_limit' => __('Gallery'), 'highlights_limit' => __('Highlights'),
    ];
    $benefits = collect($insights['benefits'] ?? [])->filter(fn ($v) => $v)->keys()->filter(fn ($k) => isset($benefitLabels[$k]))->values();
    $statusTone = ['new' => 'amber', 'contacted' => 'blue', 'closed' => 'grey'];
@endphp

@push('css')
<style>
.fd{--fd-blue:#2b5fde;--fd-ink:#0f172a;--fd-muted:#64748b;--fd-line:#e8edf5;--fd-soft:#f6f8fc;color:var(--fd-ink)}
.fd-card{background:#fff;border:1px solid var(--fd-line);border-radius:16px;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.fd-card-h{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px 20px 0}
.fd-card-h h2{font-size:15px;font-weight:700;margin:0;color:var(--fd-ink)}
.fd-card-b{padding:16px 20px 20px}
.fd-muted{color:var(--fd-muted)}
.fd-grid{display:grid;gap:18px;grid-template-columns:minmax(0,1fr)}
.fd-grid>*,.fd-kpis>*{min-width:0}
.fd .min-w-0{min-width:0}
@media (min-width:992px){.fd-g-main{grid-template-columns:minmax(0,2fr) minmax(0,1fr)}.fd-g-half{grid-template-columns:minmax(0,7fr) minmax(0,5fr)}}
/* hero */
.fd-hero{position:relative;overflow:hidden;border-radius:20px;padding:26px;color:#fff;background:linear-gradient(120deg,#1e3a8a 0%,#2b5fde 55%,#3b82f6 100%)}
.fd-hero:after{content:"";position:absolute;right:-80px;top:-80px;width:280px;height:280px;border-radius:50%;background:rgba(255,255,255,.07)}
.fd-hero-row{position:relative;z-index:1;display:flex;flex-wrap:wrap;gap:22px;align-items:center;justify-content:space-between}
.fd-id{display:flex;gap:16px;align-items:center;min-width:0}
.fd-logo{width:72px;height:72px;border-radius:16px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;box-shadow:0 6px 18px rgba(0,0,0,.18)}
.fd-logo img{width:100%;height:100%;object-fit:contain;padding:6px}
.fd-logo span{font-size:28px;font-weight:800;color:var(--fd-blue)}
.fd-hello{font-size:13px;opacity:.85;margin:0}
.fd-name{font-size:24px;font-weight:800;margin:2px 0 6px;line-height:1.2;color:#fff;word-break:break-word}
.fd-pills{display:flex;flex-wrap:wrap;gap:8px}
.fd-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 11px;font-size:12px;font-weight:700;background:rgba(255,255,255,.16);color:#fff}
.fd-pill.ok{background:#dcfce7;color:#166534}.fd-pill.warn{background:#fef3c7;color:#92400e}
.fd-plan{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.22);border-radius:16px;padding:16px 18px;min-width:260px;backdrop-filter:blur(4px)}
.fd-plan small{opacity:.8;font-size:12px}
.fd-plan strong{display:block;font-size:18px}
.fd-bar{height:6px;border-radius:99px;background:rgba(255,255,255,.25);margin:10px 0 12px;overflow:hidden}
.fd-bar i{display:block;height:100%;border-radius:99px;background:#fff}
.fd-bar.warn i{background:#fbbf24}
.fd-btn{display:inline-flex;align-items:center;gap:7px;border-radius:10px;padding:8px 14px;font-size:13px;font-weight:700;text-decoration:none!important;border:1px solid transparent;transition:.15s}
.fd-btn-white{background:#fff;color:var(--fd-blue)!important}.fd-btn-white:hover{background:#eef2ff}
.fd-btn-ghost{background:transparent;color:#fff!important;border-color:rgba(255,255,255,.5)}.fd-btn-ghost:hover{background:rgba(255,255,255,.12)}
.fd-btn-line{background:#fff;color:var(--fd-ink)!important;border-color:var(--fd-line)}.fd-btn-line:hover{border-color:var(--fd-blue);color:var(--fd-blue)!important}
@media (max-width:575.98px){.fd-hero{padding:20px}.fd-id{flex-direction:column;align-items:flex-start}.fd-name{font-size:21px}.fd-plan{min-width:0;width:100%}.fd-kpi-val{font-size:26px}}
/* alert */
.fd-alert{display:flex;gap:14px;align-items:flex-start;border-radius:14px;padding:14px 16px;background:#fffbeb;border:1px solid #fde68a;color:#78350f}
.fd-alert i{margin-top:3px}
/* kpis */
.fd-kpis{display:grid;gap:14px;grid-template-columns:repeat(2,minmax(0,1fr))}
@media (min-width:1200px){.fd-kpis{grid-template-columns:repeat(4,minmax(0,1fr))}}
.fd-kpi{padding:18px}
.fd-kpi-top{display:flex;align-items:center;justify-content:space-between}
.fd-ico{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:16px}
.t-blue{background:#e8efff;color:#2b5fde}.t-violet{background:#f1eaff;color:#7c3aed}.t-green{background:#e3f8ec;color:#15803d}.t-amber{background:#fff4dc;color:#b45309}.t-grey{background:#f1f5f9;color:#475569}
.fd-kpi-val{font-size:30px;font-weight:800;letter-spacing:-.5px;margin:12px 0 2px;line-height:1}
.fd-kpi-lbl{font-size:13px;color:var(--fd-muted);font-weight:600}
.fd-chg{font-size:12px;font-weight:700;border-radius:99px;padding:3px 8px}
.fd-chg.up{background:#e3f8ec;color:#15803d}.fd-chg.down{background:#fdecec;color:#b91c1c}.fd-chg.flat{background:#f1f5f9;color:#64748b}
/* chart */
.fd-seg{display:inline-flex;background:var(--fd-soft);border:1px solid var(--fd-line);border-radius:10px;padding:3px}
.fd-seg a{font-size:12px;font-weight:700;color:var(--fd-muted);padding:5px 10px;border-radius:8px;text-decoration:none}
.fd-seg a.on{background:#fff;color:var(--fd-blue);box-shadow:0 1px 3px rgba(15,23,42,.1)}
.fd-legend{display:flex;gap:16px;font-size:12px;color:var(--fd-muted);margin:2px 0 10px}
.fd-legend i{display:inline-block;width:10px;height:10px;border-radius:3px;margin-right:6px;vertical-align:-1px}
/* strength */
.fd-ring{--p:0;width:96px;height:96px;border-radius:50%;background:conic-gradient(var(--fd-blue) calc(var(--p)*1%),#e8edf5 0);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.fd-ring span{width:76px;height:76px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800}
.fd-check{list-style:none;margin:0;padding:0}
.fd-check li{display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px dashed var(--fd-line);font-size:13px}
.fd-check li:last-child{border-bottom:0}
.fd-check .done{color:#16a34a}.fd-check .todo{color:#cbd5e1}
.fd-check a{margin-left:auto;font-size:12px;font-weight:700;color:var(--fd-blue)}
/* enquiries */
.fd-inq{display:flex;gap:12px;align-items:flex-start;padding:12px 0;border-bottom:1px solid var(--fd-line)}
.fd-inq:last-child{border-bottom:0}
.fd-av{width:38px;height:38px;border-radius:50%;background:#e8efff;color:var(--fd-blue);font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.fd-inq p{margin:0;font-size:13px;color:var(--fd-muted)}
.fd-tag{font-size:11px;font-weight:700;border-radius:99px;padding:3px 9px;text-transform:capitalize;white-space:nowrap}
.fd-empty{text-align:center;padding:26px 10px;color:var(--fd-muted)}
.fd-empty i{font-size:28px;color:#cbd5e1;margin-bottom:8px}
/* quick actions */
.fd-actions{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
@media (min-width:576px){.fd-actions{grid-template-columns:repeat(3,minmax(0,1fr))}}
.fd-act{display:flex;flex-direction:column;gap:8px;padding:14px;border:1px solid var(--fd-line);border-radius:14px;text-decoration:none!important;color:var(--fd-ink)!important;transition:.15s;background:#fff}
.fd-act:hover{border-color:var(--fd-blue);transform:translateY(-1px);box-shadow:0 6px 16px rgba(43,95,222,.08)}
.fd-act span{font-size:13px;font-weight:700}
.fd-act small{font-size:11px;color:var(--fd-muted)}
/* plan */
.fd-chips{display:flex;flex-wrap:wrap;gap:8px}
.fd-chip{display:inline-flex;align-items:center;gap:6px;background:var(--fd-soft);border:1px solid var(--fd-line);border-radius:99px;padding:5px 11px;font-size:12px;font-weight:600}
.fd-chip i{color:#16a34a}
.fd-meter{margin-top:6px}
.fd-meter .row-l{display:flex;justify-content:space-between;font-size:12px;color:var(--fd-muted);margin-bottom:5px}
.fd-meter .track{height:6px;border-radius:99px;background:#eef2f7;overflow:hidden}
.fd-meter .track i{display:block;height:100%;background:var(--fd-blue);border-radius:99px}
</style>
@endpush

@section('clinic-content')
<div class="main-content">
  <section class="section fd">
    <div class="section-body px-1 pt-2">

      {{-- Hero: who, status, plan --}}
      <div class="fd-hero mb-4">
        <div class="fd-hero-row">
          <div class="fd-id">
            <div class="fd-logo">@if($logo)<img src="{{ $logo }}" alt="">@else<span>{{ mb_strtoupper(mb_substr($clinic->name, 0, 1)) }}</span>@endif</div>
            <div class="min-w-0">
              <p class="fd-hello">{{ __('Welcome back') }} · {{ now()->format('l, d F Y') }}</p>
              <h1 class="fd-name">{{ $clinic->name }}</h1>
              <div class="fd-pills">
                @if($clinic->specialty)<span class="fd-pill"><i class="fas fa-tooth"></i> {{ $clinic->specialty }}</span>@endif
                @if($listing['listed'])
                  <span class="fd-pill ok"><i class="fas fa-circle" style="font-size:7px"></i> {{ __('Live on MedFind') }}</span>
                @else
                  <span class="fd-pill warn"><i class="fas fa-eye-slash"></i> {{ __('Not visible yet') }}</span>
                @endif
                @if(app(\App\Services\LicenceVerification::class)->isVerified('clinic', $clinic->id))<span class="fd-pill ok"><i class="fas fa-check-circle"></i> {{ __('Licence verified') }}</span>@endif
              </div>
            </div>
          </div>
          <div class="fd-plan">
            @if($subscription)
              <small>{{ __('Your plan') }}</small>
              <strong>{{ $planName }}</strong>
              @if($daysLeft !== null)
                <div class="fd-bar {{ $expiring ? 'warn' : '' }}"><i style="width:{{ $usedPct ?? 100 }}%"></i></div>
                <small>{{ trans_choice(':count day left|:count days left', $daysLeft) }} · {{ __('renews :date', ['date' => $subscription->expires_at->format('d M Y')]) }}</small>
              @else
                <small class="d-block mt-1">{{ __('No end date') }}</small>
              @endif
            @else
              <small>{{ __('Your plan') }}</small><strong>{{ __('No active plan') }}</strong><small>{{ __('Choose a plan to appear in search.') }}</small>
            @endif
            <div class="d-flex flex-wrap mt-3" style="gap:8px">
              <a href="{{ route('clinic.subscription.status') }}" class="fd-btn fd-btn-white"><i class="fas fa-sync-alt"></i> {{ $subscription ? __('Renew / upgrade') : __('Choose a plan') }}</a>
              @if($listing['url'])<a href="{{ $listing['url'] }}" target="_blank" rel="noopener" class="fd-btn fd-btn-ghost"><i class="fas fa-external-link-alt"></i> {{ __('View my page') }}</a>@endif
            </div>
          </div>
        </div>
      </div>

      @if(!$listing['listed'])
        <div class="fd-alert mb-4"><i class="fas fa-info-circle"></i><div><strong>{{ __('Patients can\'t find your facility yet.') }}</strong> {{ implode(' · ', $listing['reasons']) }}.
          @if(!$subscription) <a href="{{ route('clinic.subscription.status') }}">{{ __('Choose a plan') }}</a>@endif</div></div>
      @elseif($expiring)
        <div class="fd-alert mb-4"><i class="fas fa-hourglass-half"></i><div><strong>{{ trans_choice('Your plan ends in :count day.|Your plan ends in :count days.', $daysLeft) }}</strong> {{ __('Renew now to stay visible. The new plan starts when the current one ends, so you lose no days.') }} <a href="{{ route('clinic.subscription.status') }}">{{ __('Renew') }}</a></div></div>
      @endif

      {{-- KPIs --}}
      <div class="fd-kpis mb-4">
        @foreach($kpis as $kpi)
          @php $delta = $kpi['before'] > 0 ? (int) round(100 * ($kpi['now'] - $kpi['before']) / $kpi['before']) : ($kpi['now'] > 0 ? null : 0); @endphp
          <div class="fd-card fd-kpi">
            <div class="fd-kpi-top">
              <div class="fd-ico t-{{ $kpi['tone'] }}"><i class="fas {{ $kpi['icon'] }}"></i></div>
              @if($delta === null)<span class="fd-chg up">{{ __('New') }}</span>
              @elseif($delta > 0)<span class="fd-chg up"><i class="fas fa-arrow-up"></i> {{ $delta }}%</span>
              @elseif($delta < 0)<span class="fd-chg down"><i class="fas fa-arrow-down"></i> {{ abs($delta) }}%</span>
              @else<span class="fd-chg flat">0%</span>@endif
            </div>
            <div class="fd-kpi-val">{{ number_format($kpi['now']) }}</div>
            <div class="fd-kpi-lbl">{{ $kpi['label'] }}</div>
          </div>
        @endforeach
      </div>
      <p class="fd-muted small mt-n2 mb-4">{{ __('Last :days days, compared with the :days days before. Each visitor is counted once a day.', ['days' => $days]) }}</p>

      {{-- Chart + profile strength --}}
      <div class="fd-grid fd-g-main mb-4">
        <div class="fd-card">
          <div class="fd-card-h">
            <h2>{{ __('How patients found you') }}</h2>
            <div class="d-flex align-items-center flex-wrap" style="gap:10px">
              <div class="fd-seg">@foreach([7, 30, 90] as $p)<a href="{{ route('clinic.dashboard', ['days' => $p]) }}" class="{{ $p === $days ? 'on' : '' }}">{{ $p }}d</a>@endforeach</div>
              <a href="{{ route('listing.report', ['type' => 'clinic', 'days' => $days]) }}" class="fd-btn fd-btn-line" title="{{ __('Download CSV report') }}"><i class="fas fa-download"></i> CSV</a>
            </div>
          </div>
          <div class="fd-card-b">
            <div class="fd-legend"><span><i style="background:#2b5fde"></i>{{ __('Profile views') }}</span><span><i style="background:rgba(34,197,94,.6)"></i>{{ __('Calls, WhatsApp & website') }}</span></div>
            <div style="height:330px"><canvas id="fd-chart"></canvas></div>
          </div>
        </div>

        <div class="fd-card">
          <div class="fd-card-h"><h2>{{ __('Profile strength') }}</h2><span class="fd-muted small">{{ collect($checklist)->where('done', true)->count() }}/{{ count($checklist) }}</span></div>
          <div class="fd-card-b">
            <div class="d-flex align-items-center mb-3" style="gap:16px">
              <div class="fd-ring" style="--p:{{ $strength }}"><span>{{ $strength }}%</span></div>
              <p class="fd-muted small mb-0">{{ $strength === 100 ? __('Excellent. Complete profiles get the most calls.') : __('Complete profiles get more calls. Finish the items below.') }}</p>
            </div>
            <ul class="fd-check">
              @foreach($checklist as $item)
                <li><i class="fas {{ $item['done'] ? 'fa-check-circle done' : 'fa-circle todo' }}"></i><span>{{ $item['label'] }}</span>@unless($item['done'])<a href="{{ $item['url'] }}">{{ __('Add') }}</a>@endunless</li>
              @endforeach
            </ul>
          </div>
        </div>
      </div>

      {{-- Enquiries + quick actions --}}
      <div class="fd-grid fd-g-half mb-4">
        <div class="fd-card">
          <div class="fd-card-h">
            <h2>{{ __('Latest enquiries') }} @if($newInquiries)<span class="fd-tag t-amber ms-1 ml-1">{{ trans_choice(':count new|:count new', $newInquiries) }}</span>@endif</h2>
            @if(!empty($insights['benefits']['inquiries']))<a href="{{ route('clinic.inquiries.index') }}" class="small fw-bold">{{ __('View all') }} <i class="fas fa-arrow-right"></i></a>@endif
          </div>
          <div class="fd-card-b pt-2">
            @forelse($recentInquiries as $inq)
              <div class="fd-inq">
                <div class="fd-av">{{ mb_strtoupper(mb_substr($inq->patient_name ?: '?', 0, 1)) }}</div>
                <div class="flex-grow-1 min-w-0">
                  <div class="d-flex justify-content-between align-items-center" style="gap:8px"><strong class="text-truncate">{{ $inq->patient_name }}</strong><span class="fd-tag t-{{ $statusTone[$inq->status] ?? 'grey' }}">{{ $inq->status }}</span></div>
                  <p class="text-truncate">{{ \Illuminate\Support\Str::limit($inq->reason, 90) }}</p>
                  <p style="font-size:12px"><i class="far fa-clock"></i> {{ $inq->created_at?->diffForHumans() }}@if($inq->phone) · <a href="tel:{{ $inq->phone }}">{{ $inq->phone }}</a>@endif</p>
                </div>
              </div>
            @empty
              <div class="fd-empty"><i class="fas fa-inbox d-block"></i>{{ __('No enquiries yet. They appear here when patients message you from your MedFind page.') }}</div>
            @endforelse
          </div>
        </div>

        <div class="fd-card">
          <div class="fd-card-h"><h2>{{ __('Quick actions') }}</h2></div>
          <div class="fd-card-b">
            <div class="fd-actions">
              <a class="fd-act" href="{{ route('clinic.edit-profile') }}"><div class="fd-ico t-blue"><i class="fas fa-pen"></i></div><span>{{ __('Edit profile') }}</span><small>{{ __('Details, services, hours') }}</small></a>
              <a class="fd-act" href="{{ route('clinic.content.index') }}"><div class="fd-ico t-violet"><i class="fas fa-images"></i></div><span>{{ __('Gallery') }}</span><small>{{ __('Photos & branches') }}</small></a>
              <a class="fd-act" href="{{ route('clinic.documents.index') }}"><div class="fd-ico t-green"><i class="fas fa-id-card"></i></div><span>{{ __('Licence') }}</span><small>{{ __('Verification documents') }}</small></a>
              <a class="fd-act" href="{{ route('clinic.inquiries.index') }}"><div class="fd-ico t-amber"><i class="fas fa-inbox"></i></div><span>{{ __('Enquiries') }}</span><small>{{ __('Reply to patients') }}</small></a>
              <a class="fd-act" href="{{ route('listing.receipts', ['type' => 'clinic']) }}"><div class="fd-ico t-grey"><i class="fas fa-file-invoice"></i></div><span>{{ __('Receipts') }}</span><small>{{ __('Download PDFs') }}</small></a>
              <a class="fd-act" href="{{ route('clinic.admin-messages.index') }}"><div class="fd-ico t-blue"><i class="fas fa-bell"></i></div><span>{{ __('Messages') }}</span><small>{{ __('From MedFind') }}</small></a>
            </div>
          </div>
        </div>
      </div>

      {{-- Plan contents --}}
      @if($subscription)
      <div class="fd-card mb-4">
        <div class="fd-card-h"><h2>{{ __('Included in your :plan plan', ['plan' => $planName]) }}</h2><a href="{{ route('clinic.subscription.status') }}" class="small fw-bold">{{ __('Compare plans') }}</a></div>
        <div class="fd-card-b">
          <div class="fd-chips mb-3">@foreach($benefits as $key)<span class="fd-chip"><i class="fas fa-check"></i>{{ $benefitLabels[$key] }}</span>@endforeach</div>
          <div class="row">
            @foreach(['services_limit' => __('Services listed'), 'gallery_limit' => __('Gallery photos')] as $key => $label)
              @if(!empty($insights['benefits'][$key]) && is_numeric($insights['benefits'][$key]))
                @php $used = (int) ($insights['usage'][$key] ?? 0); $cap = (int) $insights['benefits'][$key]; @endphp
                <div class="col-md-6 fd-meter mb-2"><div class="row-l"><span>{{ $label }}</span><span>{{ $used }} / {{ $cap }}</span></div><div class="track"><i style="width:{{ $cap ? min(100, round(100 * $used / $cap)) : 0 }}%"></i></div></div>
              @endif
            @endforeach
          </div>
        </div>
      </div>
      @endif

    </div>
  </section>
</div>
@endsection

@push('js')
<script src="{{ asset('backend/chart.js/chart.umd.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var el = document.getElementById('fd-chart'); if (!el || typeof Chart === 'undefined') return;
  var ctx = el.getContext('2d'), grad = ctx.createLinearGradient(0, 0, 0, 330);
  grad.addColorStop(0, 'rgba(43,95,222,.25)'); grad.addColorStop(1, 'rgba(43,95,222,0)');
  new Chart(el, {
    data: { labels: @json($chart['labels']), datasets: [
      { type: 'line', label: @json(__('Profile views')), data: @json($chart['views']), borderColor: '#2b5fde', backgroundColor: grad, fill: true, tension: .35, borderWidth: 3, pointRadius: 0, pointHoverRadius: 4, order: 1 },
      { type: 'bar', label: @json(__('Calls, WhatsApp & website')), data: @json($chart['contacts']), backgroundColor: 'rgba(34,197,94,.45)', hoverBackgroundColor: '#22c55e', borderRadius: 4, maxBarThickness: 12, order: 2 }
    ]},
    options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
      plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0f172a', padding: 10, cornerRadius: 8 } },
      scales: { x: { grid: { display: false }, ticks: { color: '#94a3b8', maxTicksLimit: 8, maxRotation: 0 } },
                y: { beginAtZero: true, grid: { color: '#eef2f7' }, border: { display: false }, ticks: { color: '#94a3b8', precision: 0 } } } }
  });
});
</script>
@endpush
