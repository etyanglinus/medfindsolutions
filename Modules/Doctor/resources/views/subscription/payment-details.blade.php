@extends('admin.master_layout')
@section('title')<title>{{ __('Subscription Payment Instructions') }}</title>@endsection
@section('admin-content')
<div class="main-content"><section class="section"><x-admin.breadcrumb title="{{ __('Payment Instructions') }}" :list="[__('Subscription Plans') => route('admin.subscriptions.packages.index')]" />
<div class="section-body"><div class="card"><div class="card-body"><form method="POST" action="{{ route('admin.subscriptions.payment-details.update') }}">@csrf @method('PUT')
<p class="text-muted">{{ __('These details appear to doctors and facilities before they submit their transaction code for approval.') }}</p>
<div class="form-group"><label>{{ __('M-Pesa Paybill Number') }}</label><input class="form-control" name="paybill_number" value="{{ old('paybill_number',$details?->paybill_number) }}"></div><div class="form-group"><label>{{ __('Account Number') }}</label><input class="form-control" name="account_number" value="{{ old('account_number',$details?->account_number) }}"></div><div class="form-group"><label>{{ __('Account Name') }}</label><input class="form-control" name="account_name" value="{{ old('account_name',$details?->account_name) }}"></div><div class="form-group"><label>{{ __('Instructions') }}</label><textarea class="form-control" rows="4" name="instructions">{{ old('instructions',$details?->instructions) }}</textarea></div><button class="btn btn-primary">{{ __('Save Payment Instructions') }}</button></form></div></div></div>
</section></div>
@endsection
