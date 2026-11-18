{{-- Approve / reject a paybill payment waiting for review. Expects $payment. --}}
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
