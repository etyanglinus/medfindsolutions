<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Provider\PartnerAuthController;
use App\Services\SubscriptionCouponService;
use Illuminate\Http\Request;
use Modules\Doctor\app\Models\Doctor;
use Modules\Doctor\app\Models\Clinic;
use Modules\Doctor\app\Services\SubscriptionPlanService;

class SubscriptionQuoteController extends Controller
{
    public function __invoke(Request $request, string $type)
    {
        $data = $request->validate([
            'token' => ['nullable', 'string', 'max:255'],
            'package' => ['required', 'string'],
            'coupon_code' => ['nullable', 'string', 'max:60'],
        ]);
        $model = match ($type) {
            'doctor' => Doctor::class,
            'clinic' => Clinic::class,
            default => PartnerAuthController::definition($type)['model'],
        };
        $guard = in_array($type, ['doctor', 'clinic']) ? $type : PartnerAuthController::definition($type)['guard'];
        $record = ! empty($data['token'])
            ? $model::where('registration_token', $data['token'])->firstOrFail()
            : auth($guard)->user();
        abort_unless($record, 403);
        $plan = app(SubscriptionPlanService::class)->find($type, $data['package']);
        abort_unless($plan, 422, __('Please select a valid package.'));
        $quote = empty($data['coupon_code'])
            ? ['original_price' => $plan['price'], 'discount_amount' => 0, 'price' => $plan['price']]
            : app(SubscriptionCouponService::class)->quote($data['coupon_code'], $type, $plan['id'], $record->email ?? $record->official_email, $plan['price']);
        unset($quote['coupon']);
        return response()->json($quote);
    }
}
