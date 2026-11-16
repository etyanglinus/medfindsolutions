<div class="alert alert-success small">{{ __('Pay securely through Paystack. Your subscription activates only after Paystack confirms payment.') }}</div>
@if(isset($errors) && $errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@if(!($existingCoupon ?? false))
<div class="mb-3"><label class="form-label">{{ __('Coupon code (optional)') }}</label><input class="form-control" name="coupon_code" id="checkout-coupon-code" maxlength="60" value="{{ old('coupon_code') }}"></div>
<button type="button" class="btn btn-outline-primary btn-sm mb-3" data-checkout-coupon>{{ __('Check coupon / amount') }}</button>
@endif
<div class="small mb-3" id="checkout-quote-result" role="status"></div>
<button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold" @disabled(!$paystackEnabled)><i class="fas fa-lock me-2"></i>{{ __('Continue to Paystack') }}</button>
@if(!$paystackEnabled)<p class="text-danger small mt-2">{{ __('Paystack is temporarily unavailable. Please contact Medfind Solutions.') }}</p>@endif
