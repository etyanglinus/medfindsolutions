<?php
$path = 'app/Http/Controllers/Clinic/Auth/RegisteredController.php';
$s = file_get_contents($path);
$needle = '    public function renewSubscription';
$s = substr($s, 0, strpos($s, $needle)).substr($s, strrpos($s, $needle));
file_put_contents($path, $s);
$path = 'resources/views/patient/profile/auth/partner-renewal.blade.php';
$s = str_replace("\r\n", "\n", file_get_contents($path));
$s = preg_replace('/    \/\/ Coupon apply\n.*?\n    \}\);/s', '', $s);
$s = str_replace("addEventListener('click', function () {\n        const plan", "addEventListener('click', async function () {\n        const plan", $s);
$s = str_replace('        const handler = PaystackPop.setup({', "        let quote;\n        try { quote = await window.subscriptionQuote(plan.id, coupon); } catch (error) { alert(error.message); return; }\n        if (Number(quote.price) === 0) { alert('No payment needed. Submit the manual form for review.'); return; }\n        const handler = PaystackPop.setup({", $s);
$s = str_replace('Math.round(plan.price * 100)', 'Math.round(quote.price * 100)', $s);
$include = "@include('patient.profile.auth._coupon-script', ['couponType' => \$type, 'couponToken' => null])\n";
$pos = strrpos($s, '@endsection');
$s = substr_replace($s, $include, $pos, 0);
$s = preg_replace('/(<input[^>]*name="transaction_code"[^>]*)\srequired/', '$1', $s);
if (file_put_contents($path, $s) === false) exit(1);
