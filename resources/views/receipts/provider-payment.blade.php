@php
    $meta = $subscription?->metadata ?? [];
    $original = (float) data_get($meta, 'original_price', $payment->amount);
    $discount = (float) data_get($meta, 'discount_amount', 0);
    $money = fn ($v) => $payment->currency.' '.number_format((float) $v, 2);
    $typeLabel = ['doctor' => __('Specialist'), 'clinic' => __('Facility'), 'school' => __('Medical school'), 'supplier' => __('Medical supplier')][$type] ?? '';
    $channel = data_get($payment->gateway_payload, 'verified_payment.channel');
@endphp
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>{{ __('Receipt') }} {{ $number }}</title>
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { font-size: 12px; color: #1f2937; margin: 0; }
    .wrap { padding: 36px 42px; }
    .top { width: 100%; border-bottom: 3px solid #2b5fde; padding-bottom: 14px; }
    .brand { font-size: 22px; font-weight: bold; color: #2b5fde; }
    .muted { color: #6b7280; }
    h1 { font-size: 18px; margin: 0; text-align: right; letter-spacing: 2px; }
    .paid { display: inline-block; border: 2px solid #059669; color: #059669; padding: 3px 10px; font-weight: bold; letter-spacing: 2px; margin-top: 6px; }
    table.info { width: 100%; margin-top: 22px; }
    table.info td { vertical-align: top; width: 50%; }
    .label { font-size: 10px; text-transform: uppercase; color: #6b7280; letter-spacing: 1px; margin-bottom: 4px; }
    table.items { width: 100%; border-collapse: collapse; margin-top: 26px; }
    table.items th { background: #f3f4f6; text-align: left; padding: 8px; font-size: 11px; }
    table.items td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
    .right, table.items th.right { text-align: right; }
    .total td { font-weight: bold; font-size: 14px; border-bottom: none; }
    .foot { margin-top: 40px; font-size: 10px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 10px; }
</style></head>
<body><div class="wrap">
<table class="top"><tr>
    <td><div class="brand">{{ $appName }}</div><div class="muted">{{ __('Healthcare visibility platform') }} · {{ preg_replace('#^https?://#', '', rtrim(config('app.url'), '/')) }}</div></td>
    <td class="right"><h1>{{ __('RECEIPT') }}</h1><div class="paid">{{ __('PAID') }}</div></td>
</tr></table>

<table class="info"><tr>
    <td>
        <div class="label">{{ __('Billed to') }}</div>
        <strong>{{ $client?->name ?? __('Deleted account') }}</strong><br>
        @if($typeLabel){{ $typeLabel }}<br>@endif
        {{ $client?->email ?? $client?->official_email ?? data_get($payment->gateway_payload, 'expected_email') }}
        @if($client?->phone)<br>{{ $client->phone }}@endif
    </td>
    <td class="right">
        <div class="label">{{ __('Receipt number') }}</div><strong>{{ $number }}</strong><br><br>
        <div class="label">{{ __('Date paid') }}</div>{{ ($payment->paid_at ?? $payment->updated_at)->format('d M Y, H:i') }}<br><br>
        <div class="label">{{ __('Payment reference') }}</div>{{ $payment->gateway_reference }}
    </td>
</tr></table>

<table class="items">
    <thead><tr><th>{{ __('Description') }}</th><th>{{ __('Period') }}</th><th class="right">{{ __('Amount') }}</th></tr></thead>
    <tbody>
        <tr>
            <td>{{ $subscription?->package_name ?? __('Listing subscription') }} {{ __('listing plan') }}@if($subscription?->duration_months) ({{ trans_choice(':count month|:count months', $subscription->duration_months) }})@endif
                @if(data_get($meta, 'renewal'))<br><span class="muted">{{ __('Renewal') }}</span>@endif</td>
            <td>@if($subscription?->starts_at && $subscription?->expires_at){{ $subscription->starts_at->format('d M Y') }} – {{ $subscription->expires_at->format('d M Y') }}@else — @endif</td>
            <td class="right">{{ $money($original) }}</td>
        </tr>
        @if($discount > 0)
        <tr><td colspan="2">{{ __('Discount') }}@if(data_get($meta, 'coupon_code')) ({{ __('coupon') }} {{ data_get($meta, 'coupon_code') }})@endif</td><td class="right">− {{ $money($discount) }}</td></tr>
        @endif
        <tr class="total"><td colspan="2" class="right">{{ __('Total paid') }}</td><td class="right">{{ $money($payment->amount) }}</td></tr>
    </tbody>
</table>

<p style="margin-top:18px"><span class="label">{{ __('Payment method') }}</span><br>
    @if($payment->gateway === 'coupon'){{ __('Coupon — no payment required') }}@else{{ __('Paystack') }}@if($channel) ({{ ucwords(str_replace('_', ' ', $channel)) }})@endif @endif
</p>

<div class="foot">{{ __('This receipt confirms payment received by :app for a listing subscription. Keep it for your records.', ['app' => $appName]) }} {{ __('Generated on :date.', ['date' => now()->format('d M Y')]) }}</div>
</div></body></html>
