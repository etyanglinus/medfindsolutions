<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\ProviderPaymentTransaction;
use App\Services\ProviderPaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        try {
            $payment = $service->createAttempt($client, $type, $data['package'], $data['coupon_code'] ?? null);
            if ($payment->status === ProviderPaymentTransaction::STATUS_PAID) {
                // Re-checking an earlier attempt showed it had already been paid; it is now applied.
                return redirect()->to($this->dashboardUrl($type) ?? route('login', ['type' => $type]))->with(['message' => __('Your earlier payment has been confirmed by Paystack and your subscription is active.'), 'alert-type' => 'success']);
            }
            return redirect()->away($service->initialize($payment));
        } catch (HttpException $e) {
            // Gateway down, Paystack disabled or wrong keys: send the provider back to the plan page
            // with a readable message instead of leaving them on a bare 502/503 error page.
            Log::warning('Paystack checkout could not start', ['type' => $type, 'client' => $client->id, 'status' => $e->getStatusCode(), 'error' => $e->getMessage()]);
            return redirect()->to($this->planPageUrl($type, $client))->withInput()->with([
                'message' => $e->getMessage() ?: __('Paystack checkout could not be started. Please try again.'),
                'alert-type' => 'error',
            ]);
        }
    }

    /**
     * Paystack redirects the provider here (with ?reference=...) after checkout.
     * Configure the same URL as the "Callback URL" in the Paystack dashboard.
     */
    public function callback(Request $request)
    {
        $reference = (string) ($request->query('reference') ?: $request->query('trxref'));
        $payment = $reference !== '' && strlen($reference) <= 255
            ? ProviderPaymentTransaction::with('providerProfile')->where('gateway', 'paystack')->where('gateway_reference', $reference)->first()
            : null;

        if (!$payment || !$payment->providerProfile) {
            return redirect()->route('login')->with(['message' => __('We could not find that payment. If you were charged, contact Medfind Solutions with your Paystack receipt.'), 'alert-type' => 'error']);
        }

        $profile = $payment->providerProfile;
        $type = $profile->provider_type;

        if ($request->boolean('cancelled')) {
            return redirect()->to($this->planPageUrl($type, $profile->provider))->with(['message' => __('Payment was cancelled. Choose a plan to try again.'), 'alert-type' => 'warning']);
        }

        try {
            $payment = app(ProviderPaystackService::class)->verify($reference);
        } catch (HttpException $e) {
            Log::info('Paystack callback not confirmed', ['reference' => $reference, 'error' => $e->getMessage()]);
            if ($profile->trashed()) {
                return redirect()->route('login', ['type' => $type])->with(['message' => __('This account is no longer active. Contact Medfind Solutions for payment assistance.'), 'alert-type' => 'warning']);
            }
            return redirect()->to($this->planPageUrl($type, $profile->provider))->with([
                'message' => __('Paystack has not confirmed this payment yet. If you completed payment, it will be applied automatically within a few minutes; otherwise please try again.'),
                'alert-type' => 'warning',
            ]);
        }

        if ($payment->providerProfile->trashed()) {
            return redirect()->route('login', ['type' => $type])->with(['message' => __('Payment was received after this account was deleted. Contact Medfind Solutions for payment assistance.'), 'alert-type' => 'warning']);
        }

        $success = ['message' => __('Payment confirmed by Paystack. Your subscription is now active.'), 'alert-type' => 'success'];
        if ($dashboard = $this->dashboardUrl($type)) {
            return redirect()->to($dashboard)->with($success);
        }
        return redirect()->route('login', ['type' => $type])->with(['message' => __('Payment confirmed by Paystack. Log in to complete your profile and upload your verification documents.'), 'alert-type' => 'success']);
    }

    /**
     * Signed link from the "payment received" email. Opening it proves the provider controls the
     * inbox, so the email address is marked verified (schools/suppliers cannot log in otherwise),
     * then the provider is sent to the login page for their account type.
     */
    public function access(string $type, int $id)
    {
        $client = ProviderPaystackService::model($type)::findOrFail($id);
        if (!$client->email_verified_at) {
            $client->forceFill(['email_verified_at' => now(), 'email_verified_token' => null])->save();
        }
        $email = $client->email ?? $client->official_email;
        return redirect()->route('login', ['type' => $type])->withInput(['email' => $email])->with([
            'message' => __('Your email is confirmed and your account is active. Log in with the password you chose at registration.'),
            'alert-type' => 'success',
        ]);
    }

    /** Signed "Renew now" link from emails: opens the provider's plan page, no login needed. */
    public function renewLink(string $type, int $id)
    {
        $client = ProviderPaystackService::model($type)::findOrFail($id);
        return redirect()->to(app(\App\Services\ProviderRenewalLinks::class)->planPageUrl($type, $client));
    }

    public function retry(ProviderPaymentTransaction $transaction)
    {
        checkAdminHasPermissionAndThrowException('client.payment.view');
        abort_unless($transaction->providerProfile && !$transaction->providerProfile->trashed(), 404);
        return redirect()->away(app(ProviderPaystackService::class)->initialize($transaction));
    }

    /** The page where this provider chooses a plan and pays (used for cancel/retry links). */
    private function planPageUrl(string $type, $client): string
    {
        if (!$client) return route('login');
        $loggedIn = Auth::guard($type)->id() === $client->id;

        return match ($type) {
            'doctor' => $loggedIn ? route('listing.doctor-plans') : ($client->registration_token ? route('doctor.subscription.complete', ['token' => $client->registration_token]) : route('login', ['type' => $type])),
            'clinic' => $loggedIn ? route('clinic.subscription.status') : ($client->registration_token ? route('clinic.subscription.complete', ['token' => $client->registration_token]) : route('login', ['type' => $type])),
            'school', 'supplier' => $loggedIn ? route('website.partner.subscription.renew', ['type' => $type]) : ($client->registration_token ? route('website.partner.subscription', ['type' => $type, 'token' => $client->registration_token]) : route('login', ['type' => $type])),
            default => route('login'),
        };
    }

    /** Portal home for a provider who is already signed in (renewals), otherwise null. */
    private function dashboardUrl(string $type): ?string
    {
        if (!Auth::guard($type)->check()) return null;
        return match ($type) {
            'doctor' => route('doctor.dashboard'),
            'clinic' => route('clinic.dashboard'),
            'school', 'supplier' => route('website.partner.dashboard', ['type' => $type]),
            default => null,
        };
    }
}
