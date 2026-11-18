{{-- Switch a plan on from the admin panel (also for accounts whose plan expired). Expects $type and $client. --}}
@if(checkAdminHasPermission('client.plan.activate'))
@php $activatePlans = app(\Modules\Doctor\app\Services\SubscriptionPlanService::class)->for($type); @endphp
@if($activatePlans)
<details class="mf-activate-plan mt-2">
    <summary class="btn btn-sm btn-outline-success"><i class="fas fa-bolt"></i> {{ __('Activate plan') }}</summary>
    <form method="POST" action="{{ route('admin.providers.activate-plan', ['type' => $type, 'id' => $client->id]) }}" class="border rounded p-2 mt-2 bg-light" style="min-width:240px" onsubmit="return confirm('{{ __('Activate this plan now? It starts when any current plan ends, or today if it has expired.') }}')">
        @csrf
        <label class="small mb-1">{{ __('Plan') }}</label>
        <select name="package" class="form-control form-control-sm mb-2" onchange="this.form.amount.value=this.selectedOptions[0].dataset.price">
            @foreach($activatePlans as $plan)<option value="{{ $plan['id'] }}" data-price="{{ $plan['price'] }}">{{ $plan['name'] }} · {{ $plan['duration_months'] }} {{ __('mo') }} · KES {{ number_format($plan['price']) }}</option>@endforeach
        </select>
        <label class="small mb-1">{{ __('Amount received (KES, 0 if free)') }}</label>
        <input name="amount" type="number" min="0" step="1" class="form-control form-control-sm mb-2" value="{{ $activatePlans[0]['price'] ?? 0 }}" required>
        <input name="reference" class="form-control form-control-sm mb-2" maxlength="60" placeholder="{{ __('M-Pesa or bank reference (optional)') }}">
        <input name="note" class="form-control form-control-sm mb-2" maxlength="500" placeholder="{{ __('Note (optional)') }}">
        <button class="btn btn-sm btn-success w-100">{{ __('Activate now') }}</button>
    </form>
</details>
@endif
@endif
