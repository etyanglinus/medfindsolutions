@extends('provider.partner-layout')
@section('title')<title>{{ __('Subscription & Renewal') }} – {{ $setting->app_name }}</title>@endsection

@section('partner-content')

<div class="section-header">
    <h1>{{ __('Subscription & Renewal') }}</h1>
    <div class="section-header-breadcrumb">
        {{ $type === 'school' ? __('School portal') : __('Supplier portal') }}
    </div>
</div>

@if(session('message'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('message') }}
        <button type="button" class="btn-close" data-dismiss="alert"></button>
    </div>
@endif

{{-- ── Current subscription status ── --}}
<div class="card mb-4" style="border-radius:14px;border:none;box-shadow:0 4px 20px rgba(15,23,42,.07)">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div style="width:48px;height:48px;border-radius:12px;background:var(--mf-blue-xsoft,#f0f9ff);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="fas fa-credit-card" style="color:var(--mf-blue,#0284c7);font-size:1.2rem"></i>
            </div>
            <div class="flex-grow-1">
                @if($subscription)
                    <div style="font-weight:700;color:#0f172a;font-size:1rem">
                        {{ $subscription->package_name }}
                        <span class="badge {{ $subscription->status === 'approved' ? 'bg-success' : 'bg-warning text-dark' }} ms-2" style="font-size:.7rem">
                            {{ ucfirst($subscription->status) }}
                        </span>
                    </div>
                    <div class="text-muted small mt-1">
                        <i class="fas fa-calendar-alt me-1"></i>
                        {{ __('Active through') }} <strong>{{ $subscription->expires_at?->format('d M Y') ?? __('No expiry set') }}</strong>
                        &nbsp;·&nbsp; KES {{ number_format((float) $subscription->amount, 2) }}
                    </div>
                @else
                    <div style="font-weight:700;color:#0f172a;font-size:1rem">{{ __('No active subscription') }}</div>
                    <div class="text-muted small mt-1">{{ __('Choose a package below to restore your subscribed features.') }}</div>
                @endif
            </div>
        </div>
        <p class="text-muted small mb-0 mt-3 border-top pt-3">
            <i class="fas fa-info-circle me-1"></i>
            {{ __('Renewal stacks on top of your current period — you will not lose time already paid for. Publication and verification status are not affected by renewal.') }}
        </p>
    </div>
</div>

{{-- ── Plan picker ── --}}
<div class="card mb-0" style="border-radius:14px;border:none;box-shadow:0 4px 20px rgba(15,23,42,.07)">
    <div class="card-body p-4">
        <h4 class="mb-1" style="font-weight:700;color:#0f172a">{{ __('Choose a package') }}</h4>
        <p class="text-muted small mb-4">{{ __('Pick the plan that matches your visibility goals.') }}</p>

        <div class="mf-plan-picker" id="plan-picker">
            @foreach($packages as $package)
                <label class="mf-plan-option">
                    <input type="radio" name="__plan_ui" value="{{ $package['id'] }}"
                           data-id="{{ $package['id'] }}"
                           data-price="{{ (int) round($package['price'] * 100) }}"
                           @checked($loop->first)>
                    <div class="mf-plan-option__card">
                        <div class="mf-plan-option__name">{{ $package['name'] }}</div>
                        <div style="font-size:.76rem;color:var(--mf-muted);margin:.1rem 0 .55rem">{{ $package['tagline'] ?? '' }}</div>
                        <div class="mf-plan-option__price">
                            KES {{ number_format($package['price']) }}
                            <small>/{{ $package['duration'] }}</small>
                        </div>
                        <ul class="mf-plan-option__features">
                            @foreach($package['features'] as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>
                    </div>
                </label>
            @endforeach
        </div>

        {{-- Coupon --}}
        <div class="mt-4 mb-4">
            <label class="form-label fw-600" style="font-size:.82rem;color:#0f172a">
                {{ __('Coupon code') }} <span class="text-muted fw-normal">({{ __('optional') }})</span>
            </label>
            <div class="input-group" style="max-width:340px">
                <input class="form-control rounded-start-pill px-4" id="coupon-input"
                       maxlength="60" placeholder="{{ __('e.g. RENEW20') }}">
                <button type="button" id="retired-coupon-preview"
                        class="btn btn-outline-primary rounded-end-pill px-4" style="font-weight:600">
                    {{ __('Apply') }}
                </button>
            </div>
            <div id="coupon-msg" class="small mt-1"></div>
        </div>

        {{-- ┄ Paystack ── --}}
        @include('provider.paystack-form', ['checkoutType' => $type, 'checkoutToken' => null, 'renewal' => true, 'existingCoupon' => true])


    </div>
</div>

{{-- ── Subscription & payment history ── --}}
<div class="card mt-4" style="border-radius:14px;border:none;box-shadow:0 4px 20px rgba(15,23,42,.07)">
    <div class="card-header bg-white" style="border-radius:14px 14px 0 0;border-bottom:1px solid #e2e8f0;padding:1.1rem 1.5rem">
        <h4 class="mb-0" style="font-weight:700;font-size:1rem;color:#0f172a">
            <i class="fas fa-history me-2" style="color:var(--mf-blue,#0284c7)"></i>{{ __('Subscription & payment history') }}
        </h4>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0" style="font-size:.84rem">
                <thead style="background:#f8fafc">
                    <tr>
                        <th class="px-4 py-3">{{ __('Plan') }}</th>
                        <th class="py-3">{{ __('Amount') }}</th>
                        <th class="py-3">{{ __('Status') }}</th>
                        <th class="py-3">{{ __('Period') }}</th>
                        <th class="py-3 pe-4">{{ __('Payments') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $entry)
                        <tr>
                            <td class="px-4 py-3 fw-600">{{ $entry->package_name }}</td>
                            <td class="py-3">{{ $entry->currency }} {{ number_format((float) $entry->amount, 2) }}</td>
                            <td class="py-3">
                                @php
                                    $statusClass = match($entry->status) {
                                        'approved' => 'bg-success',
                                        'pending'  => 'bg-warning text-dark',
                                        'expired'  => 'bg-secondary',
                                        'rejected' => 'bg-danger',
                                        default    => 'bg-light text-dark',
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}" style="font-size:.72rem">{{ ucfirst($entry->status) }}</span>
                            </td>
                            <td class="py-3" style="white-space:nowrap;color:#64748b">
                                {{ $entry->starts_at?->format('d M Y') ?? __('Pending') }}
                                &nbsp;–&nbsp;
                                {{ $entry->expires_at?->format('d M Y') ?? __('Not set') }}
                            </td>
                            <td class="py-3 pe-4">
                                @forelse($entry->payments as $payment)
                                    <div style="color:#64748b">
                                        <span class="badge {{ $payment->status === 'paid' ? 'bg-success' : 'bg-secondary' }}" style="font-size:.65rem">
                                            {{ ucfirst($payment->status) }}
                                        </span>
                                        {{ ucfirst(str_replace('_', ' ', $payment->gateway)) }}
                                        <span class="text-muted">· {{ $payment->created_at->format('d M Y') }}</span>
                                    </div>
                                @empty
                                    <span class="text-muted">—</span>
                                @endforelse
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">{{ __('No subscription history yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($history->hasPages())
            <div class="px-4 py-3 border-top">{{ $history->links() }}</div>
        @endif
    </div>
</div>

@include('provider.paystack-script')
@endsection
