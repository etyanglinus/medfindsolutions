@extends('admin.master_layout')
@section('title')<title>{{ __('Sales dashboard') }}</title>@endsection
@section('admin-content')
<div class="main-content"><section class="section"><div class="section-header"><h1>{{ __('Sales dashboard') }}</h1></div><div class="section-body">
@if(session('message'))<div class="alert alert-{{ ['error' => 'danger', 'warning' => 'warning'][session('alert-type')] ?? 'success' }}">{{ session('message') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@if(checkAdminHasPermission('client.payment.approve'))
<div class="card" id="payments-to-verify"><div class="card-header d-flex justify-content-between align-items-center"><h4 class="mb-0"><i class="fas fa-mobile-alt"></i> {{ __('M-Pesa payments to verify') }} @if($paybillPending->count())<span class="badge badge-warning bg-warning text-dark">{{ $paybillPending->count() }}</span>@endif</h4><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.provider-payments.index', ['gateway' => 'mpesa_paybill']) }}">{{ __('All paybill payments') }}</a></div>
<div class="card-body">
@forelse($paybillPending as $payment)
    @php $client = $payment->providerProfile?->provider; @endphp
    <div class="border rounded p-3 mb-3"><div class="row">
        <div class="col-md-5"><strong>{{ $client?->name }}</strong><div class="small text-muted">{{ ['doctor' => __('Specialist'), 'clinic' => __('Facility'), 'school' => __('School'), 'supplier' => __('Supplier')][$payment->providerProfile?->provider_type] ?? '' }} · {{ $client?->email ?? $client?->official_email }}</div><div class="small">{{ $payment->subscription?->package_name }} {{ __('plan') }}</div></div>
        <div class="col-md-3"><div class="small text-muted">{{ __('Amount to find on the statement') }}</div><div class="h5 mb-1">KES {{ number_format((float) $payment->amount) }}</div><div class="small text-muted">{{ __('M-Pesa code') }}</div><div class="h6 font-monospace text-monospace">{{ data_get($payment->gateway_payload, 'mpesa_code') }}</div>@if(data_get($payment->gateway_payload, 'payer_phone'))<div class="small">{{ __('Paid from') }} {{ data_get($payment->gateway_payload, 'payer_phone') }}</div>@endif<div class="small text-muted">{{ __('Submitted') }} {{ \Carbon\Carbon::parse(data_get($payment->gateway_payload, 'submitted_at', $payment->updated_at))->diffForHumans() }}</div></div>
        <div class="col-md-4">@include('admin.provider_payments._paybill-actions', ['payment' => $payment])</div>
    </div></div>
@empty
    <p class="text-muted mb-0">{{ __('No M-Pesa payments are waiting. New ones appear here as soon as a provider enters their code.') }}</p>
@endforelse
</div></div>
@endif
<div class="card"><div class="card-body"><p>{{ __('Onboard clients, select their subscription and send a Paystack payment invitation. Verified payment activates their account automatically.') }}</p><div class="row">@foreach(['doctor' => 'Specialist', 'clinic' => 'Facility', 'school' => 'School', 'supplier' => 'Supplier'] as $type => $label)<div class="col-md-3 mb-3"><a class="btn btn-primary btn-block" href="{{ route('admin.clients.create', ['type' => $type]) }}">{{ __('Onboard') }} {{ __($label) }}</a></div>@endforeach</div></div></div>
<div class="card"><div class="card-header"><h4>{{ __('Accounts you created') }}</h4></div><div class="card-body table-responsive"><table class="table"><thead><tr><th>{{ __('Client') }}</th><th>{{ __('Email') }}</th><th>{{ __('Type') }}</th><th>{{ __('Created') }}</th></tr></thead><tbody>@forelse($accounts as $account)<tr><td>{{ $account->client_name }}</td><td>{{ $account->client_email }}</td><td>{{ $account->provider_type === 'clinic' ? __('Facility') : ($account->provider_type === 'doctor' ? __('Specialist') : ucfirst($account->provider_type)) }}</td><td>{{ $account->created_at->format('d M Y H:i') }}</td></tr>@empty<tr><td colspan="4">{{ __('You have not onboarded clients yet.') }}</td></tr>@endforelse</tbody></table>{{ $accounts->links() }}</div></div></div></section></div>
@endsection
