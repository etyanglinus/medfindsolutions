@extends('admin.master_layout')
@section('title')<title>{{ __('Provider Payment Reviews') }}</title>@endsection
@section('admin-content')
<div class="main-content">
    <section class="section">
        <x-admin.breadcrumb title="{{ __('Provider Payment Reviews') }}" />
        <div class="section-body">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>{{ __('Provider payments') }}</h4>
                    <form method="GET"><select name="status" class="form-control" onchange="this.form.submit()">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach(['initiated', 'pending', 'paid', 'failed', 'refund_pending', 'refunded'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select></form>
                </div>
                <div class="card-body table-responsive">
                    @if(session('message'))<div class="alert alert-{{ session('alert-type') === 'error' ? 'danger' : 'success' }}">{{ session('message') }}</div>@endif
                    @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @if($paybillWaiting)<a class="btn btn-warning" href="{{ route('admin.provider-payments.index', ['gateway' => 'mpesa_paybill', 'status' => 'pending']) }}"><i class="fas fa-mobile-alt mr-1 me-1"></i>{{ trans_choice(':count M-Pesa paybill payment is waiting for review|:count M-Pesa paybill payments are waiting for review', $paybillWaiting) }}</a>@endif
                        @adminCan('setting.view')<a class="btn btn-outline-secondary" href="{{ route('admin.subscriptions.payment-details') }}"><i class="fas fa-cog mr-1 me-1"></i>{{ __('Paybill settings') }}</a>@endadminCan
                        @if(request()->hasAny(['status', 'gateway']))<a class="btn btn-link" href="{{ route('admin.provider-payments.index') }}">{{ __('Show all payments') }}</a>@endif
                    </div>
                    <table class="table">
                        <thead><tr><th>{{ __('Provider') }}</th><th>{{ __('Plan') }}</th><th>{{ __('Gateway / reference') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Gateway details') }}</th><th>{{ __('Status / action') }}</th></tr></thead>
                        <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td>{{ ucfirst($payment->providerProfile->provider_type) }}<br>{{ $payment->providerProfile->provider?->name }}<br>{{ $payment->providerProfile->provider?->email ?? $payment->providerProfile->provider?->official_email }}</td>
                                <td>{{ $payment->subscription?->package_name }}<br>{{ $payment->subscription?->duration_months }} {{ __('months') }}<br>{{ data_get($payment->subscription?->metadata, 'renewal') ? __('Renewal') : __('Initial subscription') }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $payment->gateway)) }}<br><code>{{ $payment->gateway_reference }}</code></td>
                                <td>{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</td>
                                <td>
                                    {{ data_get($payment->gateway_payload, 'bank_name') }}
                                    @if(data_get($payment->gateway_payload, 'account_number'))<br>{{ __('Account') }}: {{ data_get($payment->gateway_payload, 'account_number') }}@endif
                                    @if(data_get($payment->gateway_payload, 'expected_email'))<br>{{ data_get($payment->gateway_payload, 'expected_email') }}@endif
                                    @if($payment->gateway === 'mpesa_paybill')
                                        @if(data_get($payment->gateway_payload, 'mpesa_code'))<br>{{ __('M-Pesa code') }}: <strong class="text-monospace font-monospace">{{ data_get($payment->gateway_payload, 'mpesa_code') }}</strong>@else<br><span class="text-muted">{{ __('No M-Pesa code entered yet') }}</span>@endif
                                        @if(data_get($payment->gateway_payload, 'payer_phone'))<br>{{ __('Paid from') }}: {{ data_get($payment->gateway_payload, 'payer_phone') }}@endif
                                        @if(data_get($payment->gateway_payload, 'submitted_at'))<br><small class="text-muted">{{ __('Submitted') }} {{ \Carbon\Carbon::parse(data_get($payment->gateway_payload, 'submitted_at'))->format('d M Y H:i') }}</small>@endif
                                        @if(data_get($payment->gateway_payload, 'approved_by_name'))<br><small class="text-muted">{{ __('Approved by :name', ['name' => data_get($payment->gateway_payload, 'approved_by_name')]) }}</small>@endif
                                    @elseif($payment->gateway === 'admin')
                                        <br><small class="text-muted">{{ __('Activated by :name', ['name' => data_get($payment->gateway_payload, 'activated_by_name')]) }}</small>
                                        @if(data_get($payment->gateway_payload, 'external_reference'))<br>{{ __('Reference') }}: {{ data_get($payment->gateway_payload, 'external_reference') }}@endif
                                        @if(data_get($payment->gateway_payload, 'activation_note'))<br><small>{{ data_get($payment->gateway_payload, 'activation_note') }}</small>@endif
                                    @endif
                                    @if($payment->failure_reason)<br><small class="text-danger">{{ $payment->failure_reason }}</small>@endif
                                    @if(data_get($payment->gateway_payload, 'refund_reference'))<br>{{ __('Refund ref') }}: {{ data_get($payment->gateway_payload, 'refund_reference') }}@endif
                                </td>
                                <td>
                                    <strong>{{ $payment->gateway === 'mpesa_paybill' && $payment->status === 'pending' ? __('Waiting for review') : ($payment->gateway === 'mpesa_paybill' && $payment->status === 'initiated' ? __('Waiting for M-Pesa code') : ucfirst(str_replace('_', ' ', $payment->status))) }}</strong>
                                    @if($payment->gateway === 'mpesa_paybill' && $payment->status === 'pending')
                                        @if(checkAdminHasPermission('client.payment.approve'))
                                        <form method="POST" action="{{ route('admin.provider-payments.paybill-approve', $payment) }}" class="mt-2" onsubmit="return confirm('{{ __('Approve this payment? Confirm the code and the amount are on the bank statement first.') }}')">@csrf
                                            <input name="admin_note" class="form-control form-control-sm mb-1" maxlength="500" placeholder="{{ __('Note (optional)') }}">
                                            <button class="btn btn-sm btn-success"><i class="fas fa-check mr-1 me-1"></i>{{ __('Approve and activate') }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.provider-payments.paybill-reject', $payment) }}" class="mt-2">@csrf
                                            <input name="reason" class="form-control form-control-sm mb-1" maxlength="500" required placeholder="{{ __('Reason, e.g. code not found on statement') }}">
                                            <button class="btn btn-sm btn-outline-danger">{{ __('Reject') }}</button>
                                        </form>
                                        @endif
                                    @endif
                                    @if($payment->status === 'paid')<a class="d-block small" href="{{ route('admin.provider-payments.receipt', $payment) }}"><i class="fas fa-file-pdf"></i> {{ __('Receipt') }}</a>@endif
                                    @if(in_array($payment->status, ['initiated', 'pending']) && $payment->gateway === 'paystack')
<form method="POST" action="{{ route('admin.provider-payments.recheck', $payment) }}" class="mt-2">@csrf<button class="btn btn-sm btn-success">{{ __('Check with Paystack') }}</button></form>
@if(data_get($payment->gateway_payload, 'last_check'))<small class="d-block text-muted mt-1">{{ __('Last check') }}: {{ data_get($payment->gateway_payload, 'last_check.status') }} ({{ data_get($payment->gateway_payload, 'last_check.channel') }}) {{ \Carbon\Carbon::parse(data_get($payment->gateway_payload, 'last_check.at'))->diffForHumans() }}</small>@endif
<form method="POST" action="{{ route('admin.provider-payments.checkout', $payment) }}" class="mt-2">@csrf<button class="btn btn-sm btn-primary">{{ __('Open / Retry Paystack Checkout') }}</button></form>
@elseif(checkAdminHasPermission('client.payment.refund') && checkAdminHasPermission('setting.update') && $payment->status === 'paid' && $payment->gateway === 'paystack')
                                        <form method="POST" action="{{ route('admin.provider-payments.update', ['transaction' => $payment]) }}" class="mt-2">
                                            @csrf
                                            
                                            <input name="admin_note" required class="form-control form-control-sm mb-1" placeholder="{{ __('Refund reason / note') }}">
                                            <button name="decision" value="refund" class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('Initiate or record a full refund and revoke the subscription?') }}')">{{ __('Refund and revoke access') }}</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6">{{ __('No provider payment records found.') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                    {{ $payments->links() }}
                </div>
            </div>
            <p class="text-muted small">{{ __('Paystack confirms payments automatically. Refunds revoke subscription access. Verification and public publishing are reviewed separately.') }}</p>
        </div>
    </section>
</div>
@endsection
