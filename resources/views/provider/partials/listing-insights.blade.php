@php
    $insightType = $insightType ?? $type;
    $insightDays = in_array((int) request('days', 30), [7,30,90], true) ? (int) request('days', 30) : 30;
    $insightsService = app(\App\Services\ListingInsightsService::class);
    $insights = $insightsService->summary($insightType, $insightsService->owner($insightType), $insightDays);
@endphp
<div class="card" id="listing-insights">
    <div class="card-header d-flex justify-content-between"><h4>{{ __('Listing visibility') }}</h4>
        <a class="btn btn-outline-primary btn-sm" href="{{ route('listing.report', ['type' => $insightType, 'days' => $insightDays]) }}">{{ __('Download CSV report') }}</a>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-3"><label for="insight-days">{{ __('Reporting period') }}</label>
            <select id="insight-days" name="days" class="form-control" onchange="this.form.submit()">@foreach([7,30,90] as $period)<option value="{{ $period }}" @selected($period === $insightDays)>{{ $period }} {{ __('days') }}</option>@endforeach</select>
        </form>
        <div class="row">@foreach($insights['totals'] as $metric => $total)<div class="col"><p class="text-muted">{{ __(ucwords(str_replace('_', ' ', $metric))) }}</p><h4>{{ number_format($total) }}</h4></div>@endforeach</div>
        <p class="text-muted"><small>{{ __('Activity is counted once per visitor, listing and day for each metric. Search appearances count listings returned on directory pages. Website clicks measure outbound clicks, not confirmed website visits. Tracking starts from this update.') }}</small></p>
        <div style="height:280px"><canvas id="listing-engagement-chart"></canvas></div>
        <h5 class="mt-4">{{ __('Listing reminders') }}</h5>
        @forelse($insights['reminders'] as $reminder)<div class="alert alert-warning"><a href="{{ $reminder['url'] }}">{{ $reminder['text'] }}</a></div>@empty<p>{{ __('Your listing has no outstanding update reminders.') }}</p>@endforelse
        <h5>{{ __('Inquiry overview') }}</h5>
        @if(!empty($insights['benefits']['inquiries']))<p><a href="{{ route('listing.inbox', ['type' => $insightType]) }}">{{ __('Manage all inquiries') }}</a></p>@endif
        @foreach($insights['inquiry_counts'] as $status => $total)<span class="badge badge-info mr-2">{{ ucfirst($status) }}: {{ $total }}</span>@endforeach
        @if($insights['inquiry_counts']->isEmpty())<p>{{ __('No inquiries yet.') }}</p>@endif
        <h5 class="mt-4">{{ __('Subscription insights') }}</h5>
        <p>{{ $insights['subscription']?->package_name ?? __('No active plan') }} · {{ __('Renewal date') }}: {{ $insights['subscription']?->expires_at?->format('d M Y') ?? __('No expiry date available') }}</p>
        <ul>@foreach($insights['benefits'] as $benefit => $value)@if($value || (str_ends_with($benefit, '_limit') && is_numeric($value)))<li>{{ __(ucwords(str_replace('_', ' ', $benefit))) }}@if(is_numeric($value)): {{ isset($insights['usage'][$benefit]) ? $insights['usage'][$benefit].' / ' : '' }}{{ (int) $value === 0 && in_array($benefit, ['courses_limit', 'products_limit']) ? __('Unlimited') : $value }}@endif</li>@endif @endforeach</ul>
        <a href="{{ $insights['renewUrl'] }}" class="btn btn-outline-primary btn-sm">{{ __('View plans / renew with Paystack') }}</a>
    </div>
</div>
<script src="{{ asset('backend/chart.js/chart.umd.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const series = @json($insights['series']);
    const colors = ['#166534','#2563eb','#9333ea','#ea580c','#0891b2'];
    new Chart(document.getElementById('listing-engagement-chart'), {
        type: 'line', data: {labels: @json($insights['labels']), datasets: Object.keys(series).map(function (key, index) {
            return {label: key.replaceAll('_', ' '), data: series[key], borderColor: colors[index], tension: 0.2};
        })}, options: {responsive:true, maintainAspectRatio:false, interaction:{mode:'index', intersect:false}, scales:{y:{beginAtZero:true, ticks:{precision:0}}}}
    });
});
</script>
