<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    {{-- Reload every minute so visitors are back on the site as soon as maintenance ends. --}}
    <meta http-equiv="refresh" content="60">
    <title>{{ $setting->maintenance_title ?? __('Under maintenance') }} · {{ $setting->app_name ?? 'MedFind' }}</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; color: #1f2937; background: #f8fafc; }
        .wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; box-sizing: border-box; text-align: center; }
        .box { max-width: 560px; }
        img { max-width: 100%; height: auto; margin-bottom: 24px; }
        h1 { font-size: 28px; margin: 0 0 12px; }
        .back { display: inline-block; margin-top: 20px; padding: 10px 18px; border-radius: 999px; background: #e0ecff; color: #1e3a8a; font-weight: 600; }
        .small { margin-top: 12px; font-size: 13px; color: #6b7280; }
    </style>
</head>
<body>
<div class="wrap"><div class="box">
    @if(!empty($setting->maintenance_image))<img src="{{ asset($setting->maintenance_image) }}" alt="">@endif
    <h1>{{ $setting->maintenance_title ?? __('We are doing some maintenance') }}</h1>
    <div>{!! $setting->maintenance_description ?? '' !!}</div>
    @if(!empty($until))
        <div class="back">{{ __('We expect to be back at :time on :date', ['time' => $until->format('g:i A'), 'date' => $until->format('j M Y')]) }}</div>
        <div class="small">{{ __('This page refreshes itself; you will be taken back to the site automatically.') }}</div>
    @else
        <div class="small">{{ __('We will be back shortly. This page refreshes itself.') }}</div>
    @endif
</div></div>
</body>
</html>
