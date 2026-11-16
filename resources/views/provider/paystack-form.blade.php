<form class="mf-paystack-form" method="POST" action="{{ ($renewal ?? false) ? route('provider.paystack.renew', ['type' => $checkoutType]) : route('provider.paystack.start', ['type' => $checkoutType, 'token' => $checkoutToken]) }}" data-provider-type="{{ $checkoutType }}" data-registration-token="{{ $checkoutToken ?? '' }}">
@csrf
<input type="hidden" name="package" value="{{ $packages[0]['id'] ?? '' }}">
@if($existingCoupon ?? false)<input type="hidden" name="coupon_code">@endif
@include('provider.paystack-button')
</form>
