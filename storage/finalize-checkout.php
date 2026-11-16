<?php
$path = 'resources/views/clinic/subscription-status.blade.php';
$s = file_get_contents($path);
$s = str_replace('<input class="form-control" name="transaction_code" required>', '<input class="form-control" name="transaction_code">', $s);
$s = str_replace('<input class="form-control" name="coupon_code">', '<input class="form-control" name="coupon_code" id="coupon-input" maxlength="60"><button type="button" id="apply-coupon-btn" class="btn btn-outline-primary mt-2">{{ __(\'Apply coupon\') }}</button><div id="coupon-msg" role="status" aria-live="polite"></div>', $s);
$s = str_replace('@endsection', "@include('patient.profile.auth._coupon-script', ['couponType' => 'clinic', 'couponToken' => null])\n@endsection", $s);
file_put_contents($path, $s);
$path = 'app/Http/Controllers/Clinic/Auth/RegisteredController.php';
$s = file_get_contents($path);
$start = strpos($s, '    public function renewSubscription');
$end = strpos($s, '    public function verifyRenewalPaystack', $start);
$part = substr($s, $start, $end - $start);
$part = str_replace("'transaction_code' => ['required', 'string', 'max:255']", "'transaction_code' => ['nullable', 'string', 'max:255']", $part);
$needle = '        $subscription = $clinic->subscriptions()->firstOrCreate';
$part = str_replace($needle, "        if (\$quote['price'] > 0) {\n            \$request->validate(['transaction_code' => ['required', 'string', 'max:255']]);\n        } else {\n            \$data['transaction_code'] = 'COUPON-'.\$clinic->id.'-'.\$package['id'].'-'.\$quote['coupon']->id;\n        }\n        DB::transaction(function () use (\$clinic, \$data, \$package, \$quote) {\n".$needle, $part);
$consume = "        if (\$subscription->wasRecentlyCreated && \$quote['coupon']) app(SubscriptionCouponService::class)->consume(\$quote['coupon']);";
$part = str_replace($consume, $consume."\n        });", $part);
file_put_contents($path, substr($s, 0, $start).$part.substr($s, $end));
