@extends('layouts.patient.layout')
@section('title')<title>{{ __('Pay by M-Pesa Paybill') }} – {{ $setting->app_name }}</title>@endsection
@section('robots', 'noindex, nofollow')

@section('patient-content')
@php
    $paybillNumber = data_get($payment->gateway_payload, 'paybill_number') ?: $settings?->paybill_number;
    $accountNumber = data_get($payment->gateway_payload, 'account_number') ?: $settings?->account_number;
    $bankName = data_get($payment->gateway_payload, 'bank_name') ?: $settings?->bank_name;
    $code = data_get($payment->gateway_payload, 'mpesa_code');
@endphp
<div class="mf-public-page" style="background:var(--mf-soft);min-height:100vh">
    <div class="container py-5" style="max-width:720px">
        @if(session('message'))<div class="alert alert-{{ session('alert-type') === 'error' ? 'danger' : 'success' }}">{{ session('message') }}</div>@endif

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
                <p class="text-uppercase small fw-bold text-success mb-2"><i class="fas fa-mobile-alt me-1"></i>{{ __('M-Pesa Paybill') }}@if($bankName) · {{ $bankName }}@endif</p>
                <h1 class="h3 fw-bold mb-1">{{ $subscription?->package_name }} {{ __('plan') }}</h1>
                <p class="text-muted mb-4">{{ $client?->name }} · {{ trans_choice(':count month|:count months', (int) $subscription?->duration_months) }}</p>

                @if($payment->status === 'paid')
                    <div class="alert alert-success mb-4"><strong>{{ __('Payment confirmed.') }}</strong> {{ __('Your plan is active. A confirmation email with your receipt has been sent.') }}</div>
                    <a href="{{ $loginUrl }}" class="btn btn-primary rounded-pill px-4">{{ __('Sign in') }}</a>
                @elseif($payment->status === 'pending')
                    <div class="alert alert-info mb-0">
                        <strong>{{ __('Waiting for confirmation.') }}</strong>
                        {{ __('We received M-Pesa code :code. Our team is checking it against the bank statement; your plan switches on as soon as it is approved, usually within one working day. You will get an email.', ['code' => $code]) }}
                    </div>
                @elseif($payment->status === 'failed')
                    <div class="alert alert-warning mb-0">{{ __('This payment was closed. Please choose a plan again from your account.') }}</div>
                @else
                    @if($payment->failure_reason)
                        <div class="alert alert-warning"><strong>{{ __('We could not confirm your previous code.') }}</strong> {{ $payment->failure_reason }}</div>
                    @endif

                    <ol class="list-unstyled mb-4">
                        <li class="d-flex gap-3 mb-3"><span class="badge rounded-circle bg-success" style="width:28px;height:28px;line-height:20px">1</span>
                            <div>{{ __('On your phone open M-Pesa, then Lipa na M-Pesa, then Pay Bill.') }}</div></li>
                        <li class="d-flex gap-3 mb-3"><span class="badge rounded-circle bg-success" style="width:28px;height:28px;line-height:20px">2</span>
                            <div class="flex-grow-1">
                                <div class="row g-2">
                                    <div class="col-sm-4"><div class="border rounded-3 p-2 h-100"><div class="small text-muted">{{ __('Business number') }}</div><div class="fs-5 fw-bold">{{ $paybillNumber }}</div></div></div>
                                    <div class="col-sm-4"><div class="border rounded-3 p-2 h-100"><div class="small text-muted">{{ __('Account number') }}</div><div class="fs-5 fw-bold">{{ $accountNumber }}</div></div></div>
                                    <div class="col-sm-4"><div class="border rounded-3 p-2 h-100"><div class="small text-muted">{{ __('Amount') }}</div><div class="fs-5 fw-bold">KES {{ number_format((float) $payment->amount, 0) }}</div></div></div>
                                </div>
                                @if($settings?->account_name)<div class="small text-muted mt-2">{{ __('The SMS will show the payment to :name.', ['name' => $settings->account_name]) }}</div>@endif
                            </div></li>
                        <li class="d-flex gap-3"><span class="badge rounded-circle bg-success" style="width:28px;height:28px;line-height:20px">3</span>
                            <div>{{ __('Enter your M-Pesa PIN and confirm. Then type the code from your M-Pesa confirmation SMS below.') }}</div></li>
                    </ol>
                    @if($settings?->instructions)<div class="alert alert-light border small">{!! nl2br(e($settings->instructions)) !!}</div>@endif

                    <form method="POST" action="{{ $actionUrl }}" class="border-top pt-4">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="mpesa_code">{{ __('M-Pesa code') }}</label>
                                <input id="mpesa_code" name="mpesa_code" class="form-control form-control-lg text-uppercase @error('mpesa_code') is-invalid @enderror" maxlength="20" placeholder="SKL3XYZ9AB" value="{{ old('mpesa_code') }}" autocomplete="off" required>
                                @error('mpesa_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="payer_phone">{{ __('Phone number that paid (optional)') }}</label>
                                <input id="payer_phone" name="payer_phone" class="form-control form-control-lg" maxlength="20" placeholder="07XX XXX XXX" value="{{ old('payer_phone') }}">
                            </div>
                        </div>
                        <button class="btn btn-success btn-lg rounded-pill px-5 mt-4 fw-bold">{{ __('Submit for confirmation') }}</button>
                        <p class="small text-muted mt-3 mb-0">{{ __('Keep this page link: it shows the status of this payment at any time. Reference :ref.', ['ref' => $payment->gateway_reference]) }}</p>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
