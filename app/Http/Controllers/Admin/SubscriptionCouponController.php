<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionCoupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionCouponController extends Controller
{
    private const PROVIDERS = ['doctor', 'clinic', 'school', 'supplier'];

    public function index(): View
    {
        checkAdminHasPermissionAndThrowException('setting.view');
        $coupons = SubscriptionCoupon::latest()->paginate(30);
        return view('admin.subscription-coupons.index', compact('coupons'));
    }

    public function store(Request $request): RedirectResponse
    {
        checkAdminHasPermissionAndThrowException('setting.update');
        SubscriptionCoupon::create($this->validated($request));
        return back()->with(['message' => __('Coupon created.'), 'alert-type' => 'success']);
    }

    public function update(Request $request, SubscriptionCoupon $coupon): RedirectResponse
    {
        checkAdminHasPermissionAndThrowException('setting.update');
        $coupon->update($this->validated($request, $coupon));
        return back()->with(['message' => __('Coupon updated.'), 'alert-type' => 'success']);
    }

    public function destroy(SubscriptionCoupon $coupon): RedirectResponse
    {
        checkAdminHasPermissionAndThrowException('setting.update');
        $coupon->delete();
        return back()->with(['message' => __('Coupon deleted.'), 'alert-type' => 'success']);
    }

    private function validated(Request $request, ?SubscriptionCoupon $coupon = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'alpha_dash', 'max:60', Rule::unique('subscription_coupons', 'code')->ignore($coupon?->id)],
            'provider_types' => ['required', 'array', 'min:1'], 'provider_types.*' => [Rule::in(self::PROVIDERS)],
            'plan_codes' => ['nullable', 'string', 'max:500'], 'eligible_email' => ['nullable', 'email', 'max:255'],
            'discount_type' => ['required', Rule::in(['percent', 'fixed'])], 'discount_value' => ['required', 'numeric', 'gt:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'], 'starts_at' => ['nullable', 'date'], 'expires_at' => ['nullable', 'date', 'after:starts_at'], 'is_active' => ['nullable', 'boolean'],
        ]);
        if ($data['discount_type'] === 'percent' && $data['discount_value'] > 100) {
            throw \Illuminate\Validation\ValidationException::withMessages(['discount_value' => __('Percentage discounts cannot exceed 100%.')]);
        }
        $data['code'] = strtoupper($data['code']);
        $data['provider_types'] = array_values(array_unique($data['provider_types']));
        $data['plan_codes'] = isset($data['plan_codes']) && trim($data['plan_codes']) !== '' ? array_values(array_filter(array_map('trim', explode(',', $data['plan_codes'])))) : null;
        $data['is_active'] = $request->boolean('is_active');
        return $data;
    }
}
