@extends('admin.master_layout')
@section('title')<title>{{ __('M-Pesa Paybill Payments') }}</title>@endsection
@section('admin-content')
<div class="main-content"><section class="section"><x-admin.breadcrumb title="{{ __('M-Pesa Paybill Payments') }}" :list="[__('Subscription Plans') => route('admin.subscriptions.packages.index')]" />
<div class="section-body"><div class="row"><div class="col-lg-7">
<div class="card"><div class="card-header"><h4>{{ __('Paybill details shown to providers') }}</h4></div><div class="card-body">
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<form method="POST" action="{{ route('admin.subscriptions.payment-details.update') }}">@csrf @method('PUT')
<div class="form-group">
    <div class="custom-control custom-switch form-check form-switch">
        <input type="hidden" name="is_active" value="0">
        <input class="form-check-input custom-control-input" type="checkbox" role="switch" id="paybill-active" name="is_active" value="1" @checked(old('is_active', $details?->is_active))>
        <label class="form-check-label custom-control-label fw-bold" for="paybill-active">{{ __('Offer M-Pesa Paybill as a payment option') }}</label>
    </div>
    <small class="text-muted d-block mt-1">{{ __('When on, every plan page shows "Pay by M-Pesa Paybill" beside Paystack.') }}</small>
</div>
<div class="row">
    <div class="col-md-6 form-group"><label>{{ __('Bank') }}</label><input class="form-control" name="bank_name" placeholder="Equity Bank" value="{{ old('bank_name', $details?->bank_name) }}"></div>
    <div class="col-md-6 form-group"><label>{{ __('Paybill (business) number') }}</label><input class="form-control" name="paybill_number" inputmode="numeric" placeholder="247247" value="{{ old('paybill_number', $details?->paybill_number) }}"></div>
    <div class="col-md-6 form-group"><label>{{ __('Account number') }}</label><input class="form-control" name="account_number" value="{{ old('account_number', $details?->account_number) }}"><small class="text-muted">{{ __('The MedFind bank account number the provider types as "Account number".') }}</small></div>
    <div class="col-md-6 form-group"><label>{{ __('Account name (as shown in the M-Pesa SMS)') }}</label><input class="form-control" name="account_name" placeholder="MedFind Solutions" value="{{ old('account_name', $details?->account_name) }}"></div>
</div>
<div class="form-group"><label>{{ __('Extra instructions (optional)') }}</label><textarea class="form-control" rows="3" name="instructions" placeholder="{{ __('e.g. Payments are confirmed Monday to Saturday, 8am to 6pm.') }}">{{ old('instructions', $details?->instructions) }}</textarea></div>
<button class="btn btn-primary">{{ __('Save') }}</button>
</form></div></div>
</div><div class="col-lg-5">
<div class="card"><div class="card-header"><h4>{{ __('How paybill payments work') }}</h4></div><div class="card-body small">
<ol class="pl-3 ps-3 mb-0">
<li class="mb-2">{{ __('The provider chooses a plan and clicks "Pay by M-Pesa Paybill".') }}</li>
<li class="mb-2">{{ __('They pay the exact amount to the paybill and account above, then enter the M-Pesa code from their SMS.') }}</li>
<li class="mb-2">{{ __('The payment appears under Client Payment Reviews as "Waiting for review".') }}</li>
<li class="mb-2">{{ __('Check the code and amount on the bank statement, then click Approve. The plan switches on exactly as a Paystack payment does: account activated, confirmation email and PDF receipt.') }}</li>
<li>{{ __('If you cannot find the payment, click Reject and give the reason. The provider is emailed and can enter a corrected code.') }}</li>
</ol>
<a class="btn btn-outline-primary btn-sm mt-3" href="{{ route('admin.provider-payments.index', ['status' => 'pending']) }}">{{ __('Open payments waiting for review') }}</a>
</div></div>
</div></div></div>
</section></div>
@endsection
