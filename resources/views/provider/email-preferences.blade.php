<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>{{ __('Email preferences') }} · {{ $setting->app_name ?? config('app.name') }}</title>
<style>
body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#f3f4f6;color:#1f2937;margin:0;padding:16px}
.card{max-width:520px;margin:48px auto;background:#fff;border-radius:12px;padding:28px;box-shadow:0 1px 3px rgba(0,0,0,.08)}
h1{font-size:22px;margin:0 0 12px}.muted{color:#6b7280}.ok{background:#ecfdf5;color:#065f46;padding:10px 12px;border-radius:8px;margin-bottom:16px}
button{border:0;border-radius:24px;padding:11px 22px;font-weight:600;cursor:pointer;font-size:15px}.primary{background:#2b5fde;color:#fff}.danger{background:#dc2626;color:#fff}
a{color:#2b5fde}
</style></head>
<body><div class="card">
    <h1>{{ __('Monthly performance report') }}</h1>
    @if(session('message'))<div class="ok">{{ session('message') }}</div>@endif
    <p>{{ __('Account') }}: <strong>{{ $client->name }}</strong></p>
    @if($subscribed)
        <p>{{ __('You receive a short email at the start of each month with your listing views, calls and enquiries.') }}</p>
        <form method="POST" action="{{ $actionUrl }}">@csrf<input type="hidden" name="monthly_report" value="0"><button class="danger">{{ __('Unsubscribe from the monthly report') }}</button></form>
    @else
        <p>{{ __('You are not receiving the monthly report.') }}</p>
        <form method="POST" action="{{ $actionUrl }}">@csrf<input type="hidden" name="monthly_report" value="1"><button class="primary">{{ __('Send me the monthly report again') }}</button></form>
    @endif
    <p class="muted" style="margin-top:20px;font-size:13px">{{ __('Emails about payments, renewals, verification and your account are always sent.') }} <a href="{{ route('home') }}">{{ $setting->app_name ?? config('app.name') }}</a></p>
</div></body></html>
