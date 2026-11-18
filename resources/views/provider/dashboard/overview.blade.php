{{--
  Shared provider dashboard (specialists, facilities, schools, suppliers). Expects $dash from
  App\Services\ProviderDashboard::build(). Everything shown follows the provider's package:
  features it lacks are shown locked with an upgrade link.
--}}
@php
    $r = $dash['routes'];
    $expiring = $dash['daysLeft'] !== null && $dash['daysLeft'] <= 30;
    $barPct = $dash['planDays'] && $dash['daysLeft'] !== null ? min(100, max(4, (int) round(100 * $dash['daysLeft'] / $dash['planDays']))) : null;
    $tone = ['new' => 'amber', 'contacted' => 'blue', 'replied' => 'blue', 'quoted' => 'blue', 'reviewed' => 'blue', 'closed' => 'grey'];
    $icon = ['doctor' => 'fa-user-md', 'clinic' => 'fa-hospital', 'school' => 'fa-graduation-cap', 'supplier' => 'fa-boxes'][$dash['type']];
@endphp
@once
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
.fd-lock{position:relative}
.fd-lock-cover{position:absolute;inset:0;border-radius:16px;background:rgba(255,255,255,.82);backdrop-filter:blur(2px);display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:20px;gap:8px;z-index:2}
.fd-lock-cover i{font-size:22px;color:#94a3b8}
.fd-lock-cover strong{font-size:14px}
.fd-btn-blue{background:var(--fd-blue);color:#fff!important}.fd-btn-blue:hover{background:#1e4fc8}
.fd-chip.off{background:#fff;color:#94a3b8;border-style:dashed}.fd-chip.off i{color:#cbd5e1}
.fd-kpi.locked .fd-kpi-val{color:#cbd5e1}
.fd-section-title{font-size:13px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--fd-muted);margin:6px 0 12px}
.fd .card{border:1px solid var(--fd-line);border-radius:16px;box-shadow:0 1px 2px rgba(15,23,42,.04);overflow:hidden}
.fd .card>.card-header{background:#fff;border-bottom:1px solid var(--fd-line);padding:16px 20px;min-height:0}
.fd .card>.card-header h4{font-size:15px;font-weight:700;color:var(--fd-ink);margin:0}
.fd .table thead th{background:var(--fd-soft)!important;color:var(--fd-muted);font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;border:0}
.fd .table td{vertical-align:middle;border-color:var(--fd-line)}
.fd .table-striped tbody tr:nth-of-type(odd){background:#fbfcfe}
</style>
@endonce
<div class="fd">
  {{-- Header: who, status, plan --}}
  <div class="fd-hero mb-4">
    <div class="fd-hero-row">
      <div class="fd-id">
        <div class="fd-logo">@if($dash['logo'])<img src="{{ $dash['logo'] }}" alt="">@else<span>{{ mb_strtoupper(mb_substr($dash['name'], 0, 1)) }}</span>@endif</div>
        <div class="min-w-0">
          <p class="fd-hello">{{ __('Welcome back') }} · {{ now()->format('l, d F Y') }}</p>
          <h1 class="fd-name">{{ $dash['name'] }}</h1>
          <div class="fd-pills">
            @if($dash['subtitle'])<span class="fd-pill"><i class="fas {{ $icon }}"></i> {{ $dash['subtitle'] }}</span>@endif
            @if($dash['listing']['listed'])<span class="fd-pill ok"><i class="fas fa-circle" style="font-size:7px"></i> {{ __('Live on MedFind') }}</span>
            @else<span class="fd-pill warn"><i class="fas fa-eye-slash"></i> {{ __('Not visible yet') }}</span>@endif
            @if($dash['verified'])<span class="fd-pill ok"><i class="fas fa-check-circle"></i> {{ __('Licence verified') }}</span>@endif
          </div>
        </div>
      </div>
      <div class="fd-plan">
        <small>{{ __('Your plan') }}</small>
        @if($dash['subscription'])
          <strong>{{ $dash['planName'] }}</strong>
          @if($dash['daysLeft'] !== null)
            <div class="fd-bar {{ $expiring ? 'warn' : '' }}"><i style="width:{{ $barPct ?? 100 }}%"></i></div>
            <small>{{ trans_choice(':count day left|:count days left', $dash['daysLeft']) }} · {{ __('renews :date', ['date' => $dash['subscription']->expires_at->format('d M Y')]) }}</small>
          @else<small class="d-block mt-1">{{ __('No end date') }}</small>@endif
        @else
          <strong>{{ __('No active plan') }}</strong><small>{{ __('Choose a plan to appear in search.') }}</small>
        @endif
        <div class="d-flex flex-wrap mt-3" style="gap:8px">
          <a href="{{ $r['plans'] }}" class="fd-btn fd-btn-white"><i class="fas fa-sync-alt"></i> {{ $dash['subscription'] ? __('Renew / upgrade') : __('Choose a plan') }}</a>
          @if($dash['listing']['url'])<a href="{{ $dash['listing']['url'] }}" target="_blank" rel="noopener" class="fd-btn fd-btn-ghost"><i class="fas fa-external-link-alt"></i> {{ __('View my page') }}</a>@endif
        </div>
      </div>
    </div>
  </div>

  @if($dash['pending'])
    <div class="fd-alert mb-4" style="background:#eff6ff;border-color:#bfdbfe;color:#1e3a8a"><i class="fas fa-mobile-alt"></i><div>
      @if($dash['pending']['status'] === 'pending')<strong>{{ __('Your M-Pesa payment is being confirmed.') }}</strong> {{ __('The :plan plan switches on as soon as our team approves it, usually within one working day.', ['plan' => $dash['pending']['plan']]) }}
      @else<strong>{{ __('Your paybill payment is not finished.') }}</strong> {{ __('Enter the M-Pesa code to complete it.') }}@endif
      <a href="{{ $dash['pending']['url'] }}">{{ __('Open payment') }}</a></div></div>
  @endif
  @if(!$dash['listing']['listed'])
    <div class="fd-alert mb-4"><i class="fas fa-info-circle"></i><div><strong>{{ __('People can\'t find you on MedFind yet.') }}</strong> {{ implode(' · ', $dash['listing']['reasons']) }}.
      @if(!$dash['subscription']) <a href="{{ $r['plans'] }}">{{ __('Choose a plan') }}</a>@endif</div></div>
  @elseif($expiring)
    <div class="fd-alert mb-4"><i class="fas fa-hourglass-half"></i><div><strong>{{ trans_choice('Your plan ends in :count day.|Your plan ends in :count days.', $dash['daysLeft']) }}</strong> {{ __('Renew now to stay visible. The new plan starts when the current one ends, so you lose no days.') }} <a href="{{ $r['plans'] }}">{{ __('Renew') }}</a></div></div>
  @endif

  {{-- Headline figures --}}
  <div class="fd-kpis mb-2">
    @foreach($dash['kpis'] as $kpi)
      @if(!empty($kpi['locked']))
        <a class="fd-card fd-kpi locked text-decoration-none" href="{{ $r['plans'] }}" style="color:inherit">
          <div class="fd-kpi-top"><div class="fd-ico t-grey"><i class="fas fa-lock"></i></div><span class="fd-chg flat">{{ __('Upgrade') }}</span></div>
          <div class="fd-kpi-val">—</div><div class="fd-kpi-lbl">{{ $kpi['label'] }} · {{ __('not in your plan') }}</div>
        </a>
      @else
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
      @endif
    @endforeach
  </div>
  <p class="fd-muted small mb-4">{{ __('Last :days days, compared with the :days days before. Each visitor is counted once a day.', ['days' => $dash['days']]) }}</p>

  {{-- Chart + profile strength --}}
  <div class="fd-grid fd-g-main mb-4">
    <div class="fd-card {{ $dash['analytics'] ? '' : 'fd-lock' }}">
      @unless($dash['analytics'])
        <div class="fd-lock-cover"><i class="fas fa-chart-line"></i><strong>{{ __('Detailed analytics are not in your plan') }}</strong><span class="fd-muted small">{{ __('Upgrade to see day-by-day views and contacts, and download reports.') }}</span><a href="{{ $r['plans'] }}" class="fd-btn fd-btn-blue mt-1">{{ __('See plans') }}</a></div>
      @endunless
      <div class="fd-card-h">
        <h2>{{ __('How people found you') }}</h2>
        <div class="d-flex align-items-center flex-wrap" style="gap:10px">
          <div class="fd-seg">@foreach([7, 30, 90] as $p)<a href="{{ $r['dashboard'] }}{{ str_contains($r['dashboard'], '?') ? '&' : '?' }}days={{ $p }}" class="{{ $p === $dash['days'] ? 'on' : '' }}">{{ $p }}d</a>@endforeach</div>
          @if($dash['analytics'])<a href="{{ route('listing.report', ['type' => $dash['type'], 'days' => $dash['days']]) }}" class="fd-btn fd-btn-line" title="{{ __('Download CSV report') }}"><i class="fas fa-download"></i> CSV</a>@endif
        </div>
      </div>
      <div class="fd-card-b">
        <div class="fd-legend"><span><i style="background:#2b5fde"></i>{{ __('Profile views') }}</span><span><i style="background:rgba(34,197,94,.6)"></i>{{ __('Calls, WhatsApp & website') }}</span></div>
        <div style="height:330px"><canvas id="fd-chart"></canvas></div>
      </div>
    </div>

    <div class="fd-card">
      <div class="fd-card-h"><h2>{{ __('Profile strength') }}</h2><span class="fd-muted small">{{ collect($dash['checklist'])->where('done', true)->count() }}/{{ count($dash['checklist']) }}</span></div>
      <div class="fd-card-b">
        <div class="d-flex align-items-center mb-3" style="gap:16px">
          <div class="fd-ring" style="--p:{{ $dash['strength'] }}"><span>{{ $dash['strength'] }}%</span></div>
          <p class="fd-muted small mb-0">{{ $dash['strength'] === 100 ? __('Excellent. Complete profiles get the most calls.') : __('Complete profiles get more calls. Finish the items below.') }}</p>
        </div>
        <ul class="fd-check">
          @foreach($dash['checklist'] as $item)
            <li><i class="fas {{ $item['done'] ? 'fa-check-circle done' : 'fa-circle todo' }}"></i><span>{{ $item['label'] }}</span>@unless($item['done'])<a href="{{ $item['url'] }}">{{ __('Add') }}</a>@endunless</li>
          @endforeach
        </ul>
      </div>
    </div>
  </div>

  {{-- Enquiries + shortcuts --}}
  <div class="fd-grid fd-g-half mb-4">
    <div class="fd-card {{ $dash['inquiriesEnabled'] ? '' : 'fd-lock' }}">
      @unless($dash['inquiriesEnabled'])
        <div class="fd-lock-cover"><i class="fas fa-inbox"></i><strong>{{ __(':label are not in your plan', ['label' => $dash['enquiryLabel']]) }}</strong><span class="fd-muted small">{{ __('Upgrade to receive messages from people on your MedFind page.') }}</span><a href="{{ $r['plans'] }}" class="fd-btn fd-btn-blue mt-1">{{ __('See plans') }}</a></div>
      @endunless
      <div class="fd-card-h">
        <h2>{{ __('Latest :label', ['label' => mb_strtolower($dash['enquiryLabel'])]) }} @if($dash['newCount'])<span class="fd-tag t-amber ms-1 ml-1">{{ trans_choice(':count new|:count new', $dash['newCount']) }}</span>@endif</h2>
        @if($dash['inquiriesEnabled'])<a href="{{ $r['inquiries'] }}" class="small fw-bold">{{ __('View all') }} <i class="fas fa-arrow-right"></i></a>@endif
      </div>
      <div class="fd-card-b pt-2">
        @forelse($dash['recent'] as $inq)
          <div class="fd-inq">
            <div class="fd-av">{{ mb_strtoupper(mb_substr($inq['name'] ?: '?', 0, 1)) }}</div>
            <div class="flex-grow-1 min-w-0">
              <div class="d-flex justify-content-between align-items-center" style="gap:8px"><strong class="text-truncate">{{ $inq['name'] }}</strong><span class="fd-tag t-{{ $tone[$inq['status']] ?? 'grey' }}">{{ $inq['status'] }}</span></div>
              <p class="text-truncate">{{ \Illuminate\Support\Str::limit($inq['text'], 90) }}</p>
              <p style="font-size:12px"><i class="far fa-clock"></i> {{ $inq['at']?->diffForHumans() }}@if($inq['phone']) · <a href="tel:{{ $inq['phone'] }}">{{ $inq['phone'] }}</a>@endif</p>
            </div>
          </div>
        @empty
          <div class="fd-empty"><i class="fas fa-inbox d-block"></i>{{ __('Nothing yet. Messages appear here when people contact you from your MedFind page.') }}</div>
        @endforelse
      </div>
    </div>

    <div class="fd-card">
      <div class="fd-card-h"><h2>{{ __('Quick actions') }}</h2></div>
      <div class="fd-card-b">
        <div class="fd-actions">
          @foreach($dash['actions'] as $a)
            <a class="fd-act" href="{{ $a['url'] }}"><div class="fd-ico t-{{ $a['tone'] }}"><i class="fas {{ $a['icon'] }}"></i></div><span>{{ $a['label'] }}</span><small>{{ $a['hint'] }}</small></a>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  {{-- What the package includes --}}
  <div class="fd-card mb-4">
    <div class="fd-card-h"><h2>{{ $dash['subscription'] ? __('Included in your :plan plan', ['plan' => $dash['planName']]) : __('What a plan unlocks') }}</h2><a href="{{ $r['plans'] }}" class="small fw-bold">{{ __('Compare plans') }}</a></div>
    <div class="fd-card-b">
      @if($dash['included'])<div class="fd-chips mb-3">@foreach($dash['included'] as $label)<span class="fd-chip"><i class="fas fa-check"></i>{{ $label }}</span>@endforeach</div>@endif
      @if($dash['locked'])
        <p class="fd-section-title mb-2">{{ __('Unlock with a higher plan') }}</p>
        <div class="fd-chips mb-3">@foreach($dash['locked'] as $label)<a href="{{ $r['plans'] }}" class="fd-chip off text-decoration-none"><i class="fas fa-lock"></i>{{ $label }}</a>@endforeach</div>
      @endif
      @if($dash['meters'])
        <div class="row">
          @foreach($dash['meters'] as $m)
            <div class="col-md-6 fd-meter mb-2"><div class="row-l"><span>{{ $m['label'] }}</span><span>{{ $m['used'] }} / {{ $m['cap'] ?: __('Unlimited') }}</span></div><div class="track"><i style="width:{{ $m['cap'] ? min(100, round(100 * $m['used'] / $m['cap'])) : 8 }}%"></i></div></div>
          @endforeach
        </div>
      @endif
    </div>
  </div>
</div>

<script src="{{ asset('backend/chart.js/chart.umd.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var el = document.getElementById('fd-chart'); if (!el || typeof Chart === 'undefined') return;
  var ctx = el.getContext('2d'), grad = ctx.createLinearGradient(0, 0, 0, 330);
  grad.addColorStop(0, 'rgba(43,95,222,.25)'); grad.addColorStop(1, 'rgba(43,95,222,0)');
  new Chart(el, {
    data: { labels: @json($dash['chart']['labels']), datasets: [
      { type: 'line', label: @json(__('Profile views')), data: @json($dash['chart']['views']), borderColor: '#2b5fde', backgroundColor: grad, fill: true, tension: .35, borderWidth: 3, pointRadius: 0, pointHoverRadius: 4, order: 1 },
      { type: 'bar', label: @json(__('Calls, WhatsApp & website')), data: @json($dash['chart']['contacts']), backgroundColor: 'rgba(34,197,94,.45)', hoverBackgroundColor: '#22c55e', borderRadius: 4, maxBarThickness: 12, order: 2 }
    ]},
    options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
      plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0f172a', padding: 10, cornerRadius: 8 } },
      scales: { x: { grid: { display: false }, ticks: { color: '#94a3b8', maxTicksLimit: 8, maxRotation: 0 } },
                y: { beginAtZero: true, grid: { color: '#eef2f7' }, border: { display: false }, ticks: { color: '#94a3b8', precision: 0 } } } }
  });
});
</script>
