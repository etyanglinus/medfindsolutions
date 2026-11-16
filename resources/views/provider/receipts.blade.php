@extends($layout)
@section($contentSection)
<div class="{{ in_array($type, ['doctor', 'clinic']) ? 'main-content' : '' }}"><section class="section"><div class="section-header"><h1>{{ __('Payment receipts') }}</h1></div>
<div class="card"><div class="card-body">
<div class="table-responsive"><table class="table table-striped"><thead><tr><th>{{ __('Receipt') }}</th><th>{{ __('Date paid') }}</th><th>{{ __('Plan') }}</th><th>{{ __('Amount') }}</th><th></th></tr></thead><tbody>
@forelse($payments as $payment)
<tr><td>{{ \App\Services\PaymentReceipt::number($payment) }}<br><small class="text-muted">{{ $payment->gateway_reference }}</small></td><td>{{ ($payment->paid_at ?? $payment->updated_at)->format('d M Y') }}</td><td>{{ $payment->subscription?->package_name }}</td><td>{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('listing.receipts.download', ['type' => $type, 'transaction' => $payment->id]) }}"><i class="fas fa-file-pdf"></i> {{ __('Download PDF') }}</a></td></tr>
@empty
<tr><td colspan="5" class="text-muted">{{ __('No payments yet. A receipt appears here after each confirmed payment.') }}</td></tr>
@endforelse
</tbody></table></div>
{{ $payments->links() }}
</div></div></section></div>
@endsection
