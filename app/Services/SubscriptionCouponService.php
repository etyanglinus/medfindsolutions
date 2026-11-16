<?php

namespace App\Services;

use App\Models\SubscriptionCoupon;
use Illuminate\Validation\ValidationException;

class SubscriptionCouponService
{
    public function quote(string $code, string $providerType, string $planCode, string $email, float $price): array
    {
        $coupon = SubscriptionCoupon::whereRaw('upper(code) = ?', [strtoupper(trim($code))])->first();
        $valid = $coupon && $coupon->is_active
            && in_array($providerType, $coupon->provider_types ?? [], true)
            && (! $coupon->plan_codes || in_array($planCode, $coupon->plan_codes, true))
            && (! $coupon->eligible_email || strcasecmp($coupon->eligible_email, $email) === 0)
            && (! $coupon->starts_at || $coupon->starts_at->isPast())
            && (! $coupon->expires_at || $coupon->expires_at->isFuture())
            && (! $coupon->usage_limit || $coupon->usage_count < $coupon->usage_limit);

        if (! $valid) {
            throw ValidationException::withMessages(['coupon_code' => __('This coupon is invalid or does not apply to this account or plan.')]);
        }

        $discount = $coupon->discount_type === 'percent'
            ? round($price * min((float) $coupon->discount_value, 100) / 100, 2)
            : min((float) $coupon->discount_value, $price);

        return ['coupon' => $coupon, 'original_price' => $price, 'discount_amount' => $discount, 'price' => max(0, round($price - $discount, 2))];
    }

    public function consume(SubscriptionCoupon $coupon): void
    {
        $locked = SubscriptionCoupon::whereKey($coupon->id)->lockForUpdate()->firstOrFail();
        if (! $locked->is_active || ($locked->usage_limit && $locked->usage_count >= $locked->usage_limit)) {
            throw ValidationException::withMessages(['coupon_code' => __('This coupon has reached its usage limit.')]);
        }
        $locked->increment('usage_count');
    }
}
