@php
    $labels = \App\Services\ProviderMonthlyReportService::METRICS;
@endphp
<div style="font-family:Arial,Helvetica,sans-serif;color:#1f2937;max-width:560px">
<p>{{ __('Hello') }} {{ $providerName }},</p>
<p>{{ __('Here is how your MedFind listing performed in :month.', ['month' => $monthLabel]) }}</p>

<table cellpadding="8" style="border-collapse:collapse;width:100%;font-size:14px">
    <tr style="background:#f1f5f9;text-align:left">
        <th>{{ __('Activity') }}</th><th style="text-align:right">{{ $monthLabel }}</th><th style="text-align:right">{{ __('Change') }}</th>
    </tr>
    @foreach($labels as $key => $label)
        @php($row = $totals[$key] ?? ['current' => 0, 'previous' => 0])
        @php($diff = $row['current'] - $row['previous'])
        <tr style="border-bottom:1px solid #e5e7eb">
            <td>{{ __($label) }}</td>
            <td style="text-align:right;font-weight:bold">{{ number_format($row['current']) }}</td>
            <td style="text-align:right;color:{{ $diff > 0 ? '#15803d' : ($diff < 0 ? '#b91c1c' : '#6b7280') }}">{{ $diff > 0 ? '+' : '' }}{{ $diff === 0 ? '—' : number_format($diff) }}</td>
        </tr>
    @endforeach
</table>
<p style="font-size:12px;color:#6b7280">{{ __('Change is compared with the month before. Each person is counted once per day.') }}</p>

@if($expired)
    <p><strong>{{ __('Your :plan plan ended on :date, so your listing is no longer shown in search.', ['plan' => $planName ?? __('MedFind'), 'date' => $expiresOn]) }}</strong> {{ __('Renew to appear again.') }}</p>
    @include('emails._button', ['url' => $renewUrl, 'label' => __('Renew now')])
@elseif($renewalDue)
    <p>{{ __('Your :plan plan ends on :date. Renew now to stay visible; your new plan starts when the current one ends, so you lose no days.', ['plan' => $planName ?? __('MedFind'), 'date' => $expiresOn]) }}</p>
    @include('emails._button', ['url' => $renewUrl, 'label' => __('Renew now')])
@else
    <p>{{ __('Plan') }}: <strong>{{ $planName }}</strong>@if($expiresOn) · {{ __('valid until :date', ['date' => $expiresOn]) }}@endif</p>
    <p>{{ __('Want more people to find you? Higher plans appear first in search and unlock more profile features.') }} <a href="{{ $renewUrl }}">{{ __('Compare plans') }}</a></p>
@endif

<p>{{ __('Keep your profile, photos and opening hours up to date: complete listings get more calls.') }} <a href="{{ $dashboardUrl }}">{{ __('Open my dashboard') }}</a></p>
<p style="font-size:12px;color:#6b7280">{{ __('You receive this monthly summary because you have a listing on MedFind Solutions.') }}</p>
</div>
