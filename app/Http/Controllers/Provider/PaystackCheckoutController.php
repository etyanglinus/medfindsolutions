<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Services\ProviderPaystackService;
use App\Models\ProviderPaymentTransaction;
use Illuminate\Http\Request;

class PaystackCheckoutController extends Controller
{
    public function start(Request $request, string $type, string $token)
    {
        $model = ProviderPaystackService::model($type);
        $client = $model::where('registration_token', $token)->firstOrFail();
        return $this->checkout($request, $client, $type);
    }
    public function renew(Request $request, string $type)
    {
        ProviderPaystackService::model($type);
        $client = auth($type)->user();
        abort_unless($client, 403);
        return $this->checkout($request, $client, $type);
    }
    private function checkout(Request $request, $client, string $type)
    {
        $data = $request->validate(['package' => ['required', 'string'], 'coupon_code' => ['nullable', 'string', 'max:60']]);
        $service = app(ProviderPaystackService::class);
        $payment = $service->createAttempt($client, $type, $data['package'], $data['coupon_code'] ?? null);
        return redirect()->away($service->initialize($payment));
    }
    public function callback(Request $request)
    {
        $reference = $request->validate(['reference' => ['required', 'string', 'max:255']])['reference'];
        $payment = app(ProviderPaystackService::class)->verify($reference);
        $type = $payment->providerProfile->provider_type;
        if ($payment->providerProfile->trashed()) return redirect()->route('login', ['type' => $type])->with(['message' => __('Payment was received after this account was deleted. Contact Medfind Solutions for payment assistance.'), 'alert-type' => 'warning']);
        return redirect()->route('login', ['type' => $type])->with(['message' => __('Payment confirmed by Paystack. Log in to complete your profile and upload your verification documents.'), 'alert-type' => 'success']);
    }
    public function retry(ProviderPaymentTransaction $transaction)
    {
        checkAdminHasPermissionAndThrowException('client.payment.view');
        abort_unless($transaction->providerProfile && !$transaction->providerProfile->trashed(), 404);
        return redirect()->away(app(ProviderPaystackService::class)->initialize($transaction));
    }
}
