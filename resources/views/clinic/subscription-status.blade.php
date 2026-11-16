@extends('clinic.master_layout')
@section('title')
    <title>{{ __('My Subscription') }}</title>
@include('provider.paystack-script')
@endsection

@section('clinic-content')
<div class="main-content">
    <section class="section">
        <x-admin.breadcrumb title="{{ __('My Subscription') }}" :list="[
            __('Dashboard') => route('clinic.dashboard'),
            __('Subscription') => '#',
        ]" />

        <div class="section-body">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header"><h4>{{ __('Subscription Details') }}</h4></div>
                        <div class="card-body">
                            @if($activeSubscription)
                                <div class="alert alert-success">{{ __('Active package through :date', ['date' => $activeSubscription->expires_at?->format('d M Y') ?? __('No expiry date')]) }}</div>
                            @elseif($subscription?->expires_at && $subscription->expires_at->isPast())
                                <div class="alert alert-warning">{{ __('Your last package expired on :date. Renew to restore its features.', ['date' => $subscription->expires_at->format('d M Y')]) }}</div>
                            @endif
                            @if($subscription)
                                <table class="table table-striped">
                                    <tr>
                                        <td class="fw-bold" width="40%">{{ __('Package') }}</td>
                                        <td>{{ $subscription->package_name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">{{ __('Amount') }}</td>
                                        <td>KSh {{ number_format($subscription->package_price) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">{{ __('Duration') }}</td>
                                        <td>{{ $subscription->plan_duration }} {{ __('month(s)') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">{{ __('Payment Method') }}</td>
                                        <td>{{ ucwords(str_replace('_', ' ', $subscription->payment_method)) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">{{ __('Transaction Code') }}</td>
                                        <td><code>{{ $subscription->transaction_code }}</code></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">{{ __('Submitted') }}</td>
                                        <td>{{ $subscription->created_at->format('d M Y, H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">{{ __('Status') }}</td>
                                        <td>
                                            <span class="badge fs-6 bg-{{ $subscription->status === 'approved' ? 'success' : ($subscription->status === 'rejected' ? 'danger' : 'warning') }}">
                                                {{ __(ucfirst($subscription->status)) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @if($subscription->admin_note)
                                    <tr>
                                        <td class="fw-bold">{{ __('Admin Note') }}</td>
                                        <td>{{ $subscription->admin_note }}</td>
                                    </tr>
                                    @endif
                                    @if($subscription->approved_at)
                                    <tr>
                                        <td class="fw-bold">{{ __('Approved At') }}</td>
                                        <td>{{ $subscription->approved_at->format('d M Y, H:i') }}</td>
                                    </tr>
                                    @endif
                                </table>

                                @if($subscription->status === 'rejected')
                                    <div class="alert alert-danger mt-3">
                                        <strong>{{ __('Payment rejected.') }}</strong>
                                        {{ $subscription->admin_note ? '— ' . $subscription->admin_note : '' }}
                                        <br>
                                        <a href="{{ route('clinic.subscription.complete', ['token' => auth('clinic')->user()->registration_token]) }}"
                                            class="btn btn-sm btn-danger mt-2">
                                            {{ __('Resubmit Payment') }}
                                        </a>
                                    </div>
                                @endif

                            @else
                                <div class="alert alert-warning">
                                    {{ __('No subscription found.') }}
                                    <a href="{{ route('clinic.subscription.complete', ['token' => auth('clinic')->user()->registration_token]) }}"
                                        class="btn btn-sm btn-warning ms-2">
                                        {{ __('Submit Payment') }}
                                    </a>
                                </div>
                            @endif
                            @if($packages && (!$activeSubscription || $activeSubscription->expires_at?->isBefore(now()->addDays(14))))
                                <hr><h5>{{ __('Renew or upgrade') }}</h5>
                                @php($checkoutType = 'clinic') @php($checkoutToken = null)
<form class="mf-paystack-form" method="POST" action="{{ route('provider.paystack.renew', ['type' => 'clinic']) }}" data-provider-type="clinic">@csrf <div class="form-group"><label>{{ __('Plan') }}</label><select class="form-control" name="package">@foreach($packages as $plan)<option value="{{ $plan['id'] }}">{{ $plan['name'] }} — KES {{ number_format($plan['price']) }}</option>@endforeach</select></div>@include('provider.paystack-button', ['paystackEnabled' => \Modules\BasicPayment\app\Models\BasicPayment::where('key','paystack_status')->value('value') === 'active'])</form>
                                <small class="text-muted d-block mt-2">{{ __('Paystack confirms renewal automatically. The current active plan remains in effect until it expires.') }}</small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@include('provider.paystack-script')
@endsection
