<div class="alert alert-success small">{{ __('Pay securely through Paystack. Your subscription activates only after Paystack confirms payment.') }}</div>
@if(isset($errors) && $errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@if(!($existingCoupon ?? false))
<div class="mb-3"><label class="form-label">{{ __('Coupon code (optional)') }}</label><input class="form-control" name="coupon_code" id="checkout-coupon-code" maxlength="60" value="{{ old('coupon_code') }}"></div>
<button type="button" class="btn btn-outline-primary btn-sm mb-3" data-checkout-coupon>{{ __('Check coupon / amount') }}</button>
@endif
<div class="small mb-3" id="checkout-quote-result" role="status"></div>
<button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold" @disabled(!$paystackEnabled)><i class="fas fa-lock me-2"></i>{{ __('Continue to Paystack') }}</button>
@php $paybill = app(\App\Services\ManualPaymentService::class); $paybillSettings = $paybill->paybillEnabled() ? $paybill->settings() : null; @endphp
@if(!$paystackEnabled && !$paybillSettings)<p class="text-danger small mt-2">{{ __('Paystack is temporarily unavailable. Please contact Medfind Solutions.') }}</p>@endif
@if($paybillSettings)
<div class="mf-paybill-option border rounded-3 p-3 mt-3">
    <div class="fw-bold mb-1"><i class="fas fa-mobile-alt me-1 text-success"></i>{{ __('Or pay by M-Pesa Paybill') }}@if($paybillSettings->bank_name) <span class="text-muted fw-normal">({{ $paybillSettings->bank_name }})</span>@endif</div>
    <p class="small text-muted mb-2">{{ __('Pay from your phone to Paybill :paybill, account :account. On the next page you enter the M-Pesa code from your SMS; our team confirms it, usually within one working day.', ['paybill' => $paybillSettings->paybill_number, 'account' => $paybillSettings->account_number]) }}</p>
    <button type="submit" name="method" value="paybill" formnovalidate class="btn btn-outline-success rounded-pill px-4 fw-bold"><i class="fas fa-mobile-alt me-2"></i>{{ __('Pay by M-Pesa Paybill') }}</button>
</div>
@endif
