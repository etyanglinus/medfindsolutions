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
                                    @if($payment->failure_reason)<br><small class="text-danger">{{ $payment->failure_reason }}</small>@endif
                                    @if(data_get($payment->gateway_payload, 'refund_reference'))<br>{{ __('Refund ref') }}: {{ data_get($payment->gateway_payload, 'refund_reference') }}@endif
                                </td>
                                <td>
                                    <strong>{{ ucfirst(str_replace('_', ' ', $payment->status)) }}</strong>
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
