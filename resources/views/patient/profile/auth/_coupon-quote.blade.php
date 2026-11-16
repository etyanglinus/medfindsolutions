{{--
    Coupon quote partial
    Props: $couponType (doctor|clinic|school|supplier), $couponToken (nullable registration token)

    Behaviour:
    • Shows the original plan price, strikes it through when a coupon is applied,
      displays the discounted "You pay" price and a savings badge.
    • When the coupon brings the total to KSh 0, a "FREE — no payment needed" banner
      appears and the payment panels are hidden automatically.
    • When the coupon is cleared the payment panels come back.
--}}

{{-- ── Price summary block (updated by JS) ─────────────────────────────── --}}
<div id="coupon-price-summary" class="mb-3" style="display:none;">
    <div class="d-flex flex-wrap align-items-center gap-3">
        <div>
            <span class="text-muted small text-uppercase fw-semibold">{{ __('Was') }}</span><br>
            <span id="cps-original" class="text-decoration-line-through text-muted fs-5"></span>
        </div>
        <div>
            <i class="fas fa-arrow-right text-muted"></i>
        </div>
        <div>
            <span class="text-muted small text-uppercase fw-semibold">{{ __('Now') }}</span><br>
            <span id="cps-discounted" class="fw-bold text-success fs-4"></span>
        </div>
        <div>
            <span id="cps-savings-badge" class="badge bg-success fs-6 px-3 py-2"></span>
        </div>
    </div>
</div>

{{-- FREE banner (shown only when price === 0) --}}
<div id="coupon-free-banner" class="alert alert-success d-flex align-items-center gap-2 d-none" role="alert">
    <i class="fas fa-gift fa-lg"></i>
    <div>
        <strong>{{ __('100% covered by coupon — no payment required!') }}</strong><br>
        <span class="small">{{ __('Click "Submit" below and your account will be activated after admin review.') }}</span>
    </div>
</div>

{{-- ── Coupon input ──────────────────────────────────────────────────────── --}}
<div class="my-3" id="subscription-coupon">
    <label for="coupon-input" class="form-label fw-semibold small text-secondary">{{ __('Have a coupon code?') }}</label>
    <div class="input-group" style="max-width:420px;">
        <input id="coupon-input"
               class="form-control rounded-start"
               placeholder="{{ __('e.g. WELCOME50') }}"
               maxlength="60"
               autocomplete="off"
               value="{{ old('coupon_code') }}">
        <button type="button" class="btn btn-outline-primary" id="apply-coupon-btn">
            <i class="fas fa-tag me-1"></i>{{ __('Apply') }}
        </button>
    </div>
    <div id="coupon-msg" role="status" aria-live="polite" class="mt-2 small"></div>
    @error('coupon_code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
</div>

@include('patient.profile.auth._coupon-script', [
    'couponType'  => $couponType,
    'couponToken' => $couponToken ?? null,
])
