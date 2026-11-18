<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\ProviderPaymentTransaction;
use App\Services\ManualPaymentService;
use Illuminate\Http\Request;

/**
 * The provider's M-Pesa paybill page: how much to pay, where, and a box for the M-Pesa code.
 * The link is signed (it is in emails and works without signing in) and names one payment.
 */
class PaybillPaymentController extends Controller
{
    public function show(ProviderPaymentTransaction $transaction, ManualPaymentService $manual)
    {
        abort_unless($transaction->gateway === ManualPaymentService::GATEWAY_PAYBILL, 404);
        $transaction->loadMissing('subscription', 'providerProfile');
        $type = $transaction->providerProfile?->provider_type;
        return view('provider.paybill-payment', [
            'payment' => $transaction,
            'subscription' => $transaction->subscription,
            'client' => $transaction->providerProfile?->provider,
            'type' => $type,
            'settings' => $manual->settings(),
            'actionUrl' => request()->fullUrl(),
            'loginUrl' => route('login', ['type' => $type]),
        ]);
    }

    public function submit(Request $request, ProviderPaymentTransaction $transaction, ManualPaymentService $manual)
    {
        $data = $request->validate(['mpesa_code' => ['required', 'string', 'max:20'], 'payer_phone' => ['nullable', 'string', 'max:20']]);
        $manual->submitCode($transaction, $data['mpesa_code'], $data['payer_phone'] ?? null);
        return redirect()->to($request->fullUrl())->with(['message' => __('Thank you. Your M-Pesa code has been sent to our team for confirmation. Your plan switches on as soon as it is approved, usually within one working day, and you will get an email.'), 'alert-type' => 'success']);
    }
}
