<?php
function editFile($path, $callback) {
    $text = str_replace("\r\n", "\n", file_get_contents($path));
    file_put_contents($path, $callback($text));
}
foreach (['Doctor', 'Clinic'] as $kind) {
    $type = strtolower($kind);
    editFile("app/Http/Controllers/$kind/Auth/RegisteredController.php", function ($s) use ($kind, $type) {
        $start = strpos($s, '    public function verifyPaystackSubscription');
        $end = strpos($s, '    public function storeSubscription', $start);
        $part = substr($s, $start, $end - $start);
        $part = str_replace("'reference' =>", "'coupon_code' => ['nullable', 'string', 'max:60'],\n            'reference' =>", $part);
        $line = '$package = collect($packages)->firstWhere(\'id\', $request->package);';
        $quote = '$quote = $request->filled(\'coupon_code\') ? app(SubscriptionCouponService::class)->quote($request->coupon_code, \''.$type.'\', $package[\'id\'], $'.$type.'->email, (float) $package[\'price\']) : [\'coupon\' => null, \'original_price\' => $package[\'price\'], \'discount_amount\' => 0, \'price\' => $package[\'price\']];';
        $part = str_replace($line, $line."\n        ".$quote, $part);
        $part = str_replace("round(\$package['price'] * 100)", "round(\$quote['price'] * 100)", $part);
        $part = preg_replace('/(\x27package_price\x27\s*=> )\$package\[\x27price\x27\],/', '$1$quote[\'price\'],\n                \'original_price\' => $quote[\'original_price\'],\n                \'discount_amount\' => $quote[\'discount_amount\'],\n                \'coupon_code\' => $quote[\'coupon\']?->code,', $part);
        // preg_replace replacement above uses literal newlines.
        $part = str_replace('\\n', "\n", $part);
        if ($kind === 'Doctor') {
            $part = str_replace('        DoctorSubscription::firstOrCreate(', "        DB::transaction(function () use (\$doctor, \$package, \$quote, \$payment, \$request) {\n        \$subscription = DoctorSubscription::firstOrCreate(", $part);
            $part = str_replace("        \$doctor->update(['status' => DoctorStatus::ACTIVE->value]);", "        if (\$subscription->wasRecentlyCreated && \$quote['coupon']) app(SubscriptionCouponService::class)->consume(\$quote['coupon']);\n        \$doctor->update(['status' => DoctorStatus::ACTIVE->value]);\n        });", $part);
        } else {
            $part = str_replace('use ($clinic, $package, $payment, $request)', 'use ($clinic, $package, $quote, $payment, $request)', $part);
            $part = str_replace('            ClinicSubscription::firstOrCreate(', '            $subscription = ClinicSubscription::firstOrCreate(', $part);
            $part = str_replace("            \$clinic->update(['status' => 1]);", "            if (\$subscription->wasRecentlyCreated && \$quote['coupon']) app(SubscriptionCouponService::class)->consume(\$quote['coupon']);\n            \$clinic->update(['status' => 1]);", $part);
        }
        $s = substr($s, 0, $start).$part.substr($s, $end);
        // Manual submissions and coupon usage must commit or roll back together.
        $start = strpos($s, '    public function storeSubscription');
        $end = strpos($s, '\n    public function ', $start + 10);
        if ($end === false) $end = strpos($s, '    private function packageOptions', $start);
        $part = substr($s, $start, $end - $start);
        $part = str_replace("'transaction_code' => ['required', 'string', 'max:255']", "'transaction_code' => ['nullable', 'string', 'max:255']", $part);
        $needle = '        $subscription = '.$kind.'Subscription::firstOrCreate(';
        $before = "        if (\$quote['price'] > 0) {\n            \$request->validate(['transaction_code' => ['required', 'string', 'max:255']]);\n        } else {\n            \$request->merge(['transaction_code' => 'COUPON-'.\$".$type."->id.'-'.\$package['id'].'-'.\$quote['coupon']->id]);\n        }\n        DB::transaction(function () use (\$".$type.", \$package, \$quote, \$request) {\n";
        $part = str_replace($needle, $before.$needle, $part);
        $consume = "        if (\$subscription->wasRecentlyCreated && \$quote['coupon']) app(SubscriptionCouponService::class)->consume(\$quote['coupon']);";
        $part = str_replace($consume, $consume."\n        });", $part);
        return substr($s, 0, $start).$part.substr($s, $end);
    });
}
editFile('app/Http/Controllers/Provider/PartnerSubscriptionController.php', function ($s) {
    $s = preg_replace('/(\$package\s*= collect\(\$packages\)->firstWhere\(\x27id\x27, \$validated\[\x27package\x27\]\);)/', '$1'."\n".'        $quote = ! empty($validated[\'coupon_code\']) ? app(SubscriptionCouponService::class)->quote($validated[\'coupon_code\'], $type, $package[\'id\'], $record->official_email, (float) $package[\'price\']) : [\'coupon\' => null, \'original_price\' => $package[\'price\'], \'discount_amount\' => 0, \'price\' => $package[\'price\']];', $s);
    $s = str_replace('use ($profile, $package, $validated,', 'use ($profile, $package, $quote, $validated,', $s);
    $s = preg_replace('/(\x27amount\x27\s*=> )\$package\[\x27price\x27\]/', '$1$quote[\'price\']', $s);
    $s = str_replace("round(\$package['price'] * 100)", "round(\$quote['price'] * 100)", $s);
    $s = str_replace("'duration_months'     => \$package['duration_months'],", "'duration_months'     => \$package['duration_months'],\n                'metadata' => ['entitlements' => \$package['entitlements'], 'coupon_code' => \$quote['coupon']?->code, 'original_price' => \$quote['original_price'], 'discount_amount' => \$quote['discount_amount']],", $s);
    $s = str_replace('            $subscription = ProviderSubscription::create([', "            if (\$quote['coupon']) app(SubscriptionCouponService::class)->consume(\$quote['coupon']);\n            \$subscription = ProviderSubscription::create([", $s);
    $s = str_replace("'transaction_code' => ['required', 'string', 'max:255']", "'transaction_code' => ['nullable', 'string', 'max:255']", $s);
    $needle = '        $idempotencyKey =';
    $s = str_replace($needle, "        if (\$quote['price'] > 0) {\n            \$request->validate(['transaction_code' => ['required', 'string', 'max:255']]);\n        } else {\n            \$validated['transaction_code'] = 'COUPON-'.\$profile->id.'-'.\$package['id'].'-'.\$quote['coupon']->id;\n        }\n".$needle, $s);
    return $s;
});
foreach (['partner-subscription', 'partner-renewal'] as $view) {
    editFile("resources/views/patient/profile/auth/$view.blade.php", function ($s) use ($view) {
        $s = preg_replace('/    \/\/ (Apply coupon[^\n]*|Coupon apply)\n.*?\n    \}\);/s', '', $s);
        $s = str_replace("addEventListener('click', function () {\n        const plan", "addEventListener('click', async function () {\n        const plan", $s);
        $s = str_replace('        const handler = PaystackPop.setup({', "        let quote;\n        try { quote = await window.subscriptionQuote(plan.id, coupon); } catch (error) { alert(error.message); return; }\n        if (Number(quote.price) === 0) { alert('No payment needed. Submit the manual form for review.'); return; }\n        const handler = PaystackPop.setup({", $s);
        $s = str_replace('Math.round(plan.price * 100)', 'Math.round(quote.price * 100)', $s);
        $include = "@include('patient.profile.auth._coupon-script', ['couponType' => \$type, 'couponToken' => ".($view === 'partner-subscription' ? '$record->registration_token' : 'null')."])\n";
        if ($view === 'partner-subscription') {
            $s = str_replace('<script src="https://js.paystack.co/v1/inline.js"></script>\n<script>', '<script src="https://js.paystack.co/v1/inline.js"></script>\n@endif\n<script>', $s);
            // Use actual newlines for this boundary.
            $s = str_replace("<script src=\"https://js.paystack.co/v1/inline.js\"></script>\n<script>", "<script src=\"https://js.paystack.co/v1/inline.js\"></script>\n@endif\n<script>", $s);
            $s = str_replace("</script>\n@endif\n@endsection", "</script>\n".$include."@endsection", $s);
        } else $s = str_replace('@endsection', $include.'@endsection', $s);
        $s = preg_replace('/(<input[^>]*name="transaction_code"[^>]*)\srequired/', '$1', $s);
        return $s;
    });
}
foreach (['doctor', 'clinic'] as $type) {
    editFile("resources/views/patient/profile/auth/$type-subscription.blade.php", function ($s) use ($type) {
        $include = "@include('patient.profile.auth._coupon-quote', ['couponType' => '$type', 'couponToken' => \$".$type."->registration_token])\n";
        $needle = $type === 'doctor' ? '                            <hr class="my-4">' : "                            <div class=\"row\">\n                                <div class=\"col-12 mb-4\">";
        $s = str_replace($needle, $include.$needle, $s);
        $s = str_replace('function payWithPaystack()', 'async function payWithPaystack()', $s);
        $s = str_replace('function payClinicWithPaystack()', 'async function payClinicWithPaystack()', $s);
        $selected = $type === 'doctor' ? 'selectedPkg' : 'selectedPackage';
        $needle = $type === 'doctor' ? '    var price    = parseInt(selectedPkg.dataset.price) * 100;' : '        var handler = PaystackPop.setup({';
        $addition = "    var coupon = document.getElementById('coupon-input').value.trim();\n    var quote;\n    try { quote = await window.subscriptionQuote(\$$selected.value, coupon); } catch (error) { alert(error.message); return; }\n    if (Number(quote.price) === 0) { alert('No payment needed. Submit the manual form for review.'); return; }\n";
        $addition = str_replace('$'.$selected, $selected, $addition);
        $s = str_replace($needle, $addition.($type === 'doctor' ? '    var price = Math.round(quote.price * 100);' : $needle), $s);
        $s = str_replace('parseInt(selectedPackage.dataset.price) * 100', 'Math.round(quote.price * 100)', $s);
        $s = str_replace('reference: response.reference, package:', 'reference: response.reference, coupon_code: coupon, package:', $s);
        return $s;
    });
}
