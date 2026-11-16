<?php

namespace App\Services;

use App\Models\ProviderProfile;
use App\Models\ProviderSubscription;
use App\Models\ProviderPaymentTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\BasicPayment\app\Models\BasicPayment;
use Modules\Doctor\app\Services\SubscriptionPlanService;

class ProviderPaystackService
{
    public static function model(string $type): string
    {
        return match ($type) {
            'doctor' => \Modules\Doctor\app\Models\Doctor::class,
            'clinic' => \Modules\Doctor\app\Models\Clinic::class,
            'school' => \Modules\HealthcareInstitution\app\Models\HealthcareInstitution::class,
            'supplier' => \Modules\MedicalSupplier\app\Models\MedicalSupplier::class,
            default => abort(404),
        };
    }

    public function secret(): string
    {
        $secret = BasicPayment::where('key', 'paystack_secret_key')->value('value');
        abort_unless($secret && BasicPayment::where('key', 'paystack_status')->value('value') === 'active', 503, __('Paystack is not configured or is disabled.'));
        return $secret;
    }

    public function createAttempt(Model $client, string $type, string $code, ?string $couponCode = null): ProviderPaymentTransaction
    {
        $this->secret();
        $plan = app(SubscriptionPlanService::class)->find($type, $code);
        if (!$plan) throw ValidationException::withMessages(['package' => __('Choose an available subscription plan.')]);
        if ($existing = $this->pendingAttempt($client, $type, $code, $couponCode)) {
            if (!$this->isStale($existing)) return $existing;
            // An old attempt's Paystack checkout may have expired. Ask Paystack: if it was paid, settle it;
            // if it expired, close it and fall through to a fresh attempt with a new reference.
            try { $this->reconcile($existing); } catch (\Symfony\Component\HttpKernel\Exception\HttpException) { return $existing; }
            $existing->refresh();
            if ($existing->status !== ProviderPaymentTransaction::STATUS_FAILED) return $existing;
        }
        $email = $client->email ?? $client->official_email;
        $quote = $couponCode ? app(SubscriptionCouponService::class)->quote($couponCode, $type, $code, $email, $plan['price']) : ['coupon' => null, 'price' => $plan['price'], 'original_price' => $plan['price'], 'discount_amount' => 0];
        if ($quote['price'] <= 0) return $this->activateFree($client, $type, $plan, $quote, $email);
        return DB::transaction(function () use ($client, $type, $plan, $quote, $email) {
            $profile = ProviderProfile::firstOrCreate(['provider_type' => $type, 'provider_id' => $client->id], ['status' => 'pending_payment']);
            $profile = ProviderProfile::whereKey($profile->id)->lockForUpdate()->firstOrFail();
            if ($existing = $this->pendingAttempt($client, $type, $plan['id'], $quote['coupon']?->code)) return $existing;
            if (in_array($type, ['school', 'supplier']) && !$client->provider_profile_id) $client->update(['provider_profile_id' => $profile->id]);
            if ($quote['coupon']) app(SubscriptionCouponService::class)->consume($quote['coupon']);
            $subscription = ProviderSubscription::create(['provider_profile_id' => $profile->id, 'plan_code' => $plan['id'], 'package_name' => $plan['name'], 'amount' => $quote['price'], 'currency' => 'KES', 'duration_months' => $plan['duration_months'], 'status' => 'pending',
                'metadata' => ['entitlements' => $plan['entitlements'], 'coupon_code' => $quote['coupon']?->code, 'coupon_consumed' => (bool) $quote['coupon'], 'original_price' => $quote['original_price'], 'discount_amount' => $quote['discount_amount']]]);
            $reference = 'MFS-'.Str::uuid();
            return ProviderPaymentTransaction::create(['provider_profile_id' => $profile->id, 'provider_subscription_id' => $subscription->id, 'gateway' => 'paystack', 'gateway_reference' => $reference, 'idempotency_key' => 'paystack:'.$reference, 'amount' => $quote['price'], 'currency' => 'KES', 'status' => 'initiated', 'gateway_payload' => ['expected_email' => $email]]);
        });
    }

    public function initialize(ProviderPaymentTransaction $payment): string
    {
        abort_unless($payment->gateway === 'paystack' && in_array($payment->status, ['initiated', 'pending']), 422);
        if ($url = data_get($payment->gateway_payload, 'authorization_url')) return $url;
        $response = Http::withToken($this->secret())->timeout(30)->post('https://api.paystack.co/transaction/initialize', [
            'email' => data_get($payment->gateway_payload, 'expected_email') ?: ($payment->providerProfile->provider->email ?? $payment->providerProfile->provider->official_email),
            'amount' => (int) round((float) $payment->amount * 100), 'currency' => $payment->currency,
            'reference' => $payment->gateway_reference,
            // Where Paystack sends the provider after paying. This per-transaction value overrides the
            // dashboard "Callback URL", so payments return to this site even if the dashboard is blank.
            'callback_url' => self::callbackUrl(),
            'metadata' => [
                // Pressing "Cancel" on the Paystack page brings the provider back to their plan page.
                'cancel_action' => self::callbackUrl(['reference' => $payment->gateway_reference, 'cancelled' => 1]),
                'provider_type' => $payment->providerProfile?->provider_type,
                'plan_code' => $payment->subscription?->plan_code,
                'custom_fields' => array_values(array_filter([
                    ['display_name' => 'Account type', 'variable_name' => 'provider_type', 'value' => (string) $payment->providerProfile?->provider_type],
                    ['display_name' => 'Plan', 'variable_name' => 'plan', 'value' => (string) $payment->subscription?->package_name],
                ], fn ($field) => $field['value'] !== '')),
            ],
        ]);
        $url = $response->json('data.authorization_url');
        abort_unless($response->successful() && $response->json('status') === true && is_string($url) && parse_url($url, PHP_URL_SCHEME) === 'https' && parse_url($url, PHP_URL_HOST) === 'checkout.paystack.com', 502, __('Paystack checkout could not be started. Please try again.'));
        $payment->update(['gateway_payload' => array_merge($payment->gateway_payload ?? [], ['authorization_url' => $url, 'access_code' => $response->json('data.access_code')])]);
        return $url;
    }

    /** Public URL Paystack redirects the payer to after checkout. */
    public static function callbackUrl(array $query = []): string
    {
        return route('provider.paystack.callback', $query);
    }

    /** Public URL Paystack POSTs signed events (charge.success) to. Set it as the dashboard "Webhook URL". */
    public static function webhookUrl(): string
    {
        return route('api.provider-payments.paystack.webhook');
    }

    public function verify(string $reference): ProviderPaymentTransaction
    {
        $payment = ProviderPaymentTransaction::where('gateway', 'paystack')->where('gateway_reference', $reference)->firstOrFail();
        $response = Http::withToken($this->secret())->timeout(30)->get('https://api.paystack.co/transaction/verify/'.rawurlencode($reference));
        abort_unless($response->successful() && $response->json('status') === true && $response->json('data.status') === 'success', 422, __('Paystack has not confirmed this payment.'));
        abort_unless($response->json('data.reference') === $reference, 422, __('Paystack returned a different payment reference.'));
        $this->settle($response->json('data'));
        $payment->refresh();
        abort_unless($payment->status === 'paid', 422, __('Payment could not be confirmed.'));
        return $payment;
    }

    /**
     * Ask Paystack for the current state of a stuck attempt and apply it if it has been paid.
     * Safety net for M-Pesa / bank transfers that finish after the payer left the checkout page,
     * and for sites where the webhook is not (yet) configured. Never marks anything paid unless
     * Paystack itself reports success for the exact reference, amount, currency and email.
     *
     * @return string Paystack's transaction status (success, ongoing, abandoned, failed, ...) or "already_paid".
     */
    public function reconcile(ProviderPaymentTransaction $payment): string
    {
        abort_unless($payment->gateway === 'paystack', 422);
        if ($payment->status === 'paid') return 'already_paid';
        $response = Http::withToken($this->secret())->timeout(30)->get('https://api.paystack.co/transaction/verify/'.rawurlencode($payment->gateway_reference));
        abort_unless($response->successful() && $response->json('status') === true && $response->json('data.reference') === $payment->gateway_reference, 502, __('Paystack could not be reached to check this payment.'));
        $status = (string) $response->json('data.status');
        $payment->update(['gateway_payload' => array_merge($payment->gateway_payload ?? [], ['last_check' => [
            'at' => now()->toIso8601String(), 'status' => $status, 'channel' => $response->json('data.channel'), 'gateway_response' => $response->json('data.gateway_response'),
        ]])]);
        if ($status === 'success') {
            $this->settle($response->json('data'));
        } elseif (in_array($status, ['failed', 'reversed'], true) || ($status === 'abandoned' && $this->isStale($payment))) {
            $this->closeUnpaid($payment, (string) ($response->json('data.gateway_response') ?: $status));
        }
        return $status;
    }

    /** Attempts older than this are re-checked with Paystack before being reused or kept open. */
    private function isStale(ProviderPaymentTransaction $payment): bool
    {
        return $payment->created_at && $payment->created_at->lte(now()->subMinutes(30));
    }

    /**
     * Paystack says this checkout ended without payment (expired, failed or reversed): mark the attempt
     * failed, cancel its pending subscription and release any coupon, so the provider's next click
     * starts a new checkout. If money still arrives for it later, settle() accepts it.
     */
    private function closeUnpaid(ProviderPaymentTransaction $payment, string $reason): void
    {
        DB::transaction(function () use ($payment, $reason) {
            $payment = ProviderPaymentTransaction::whereKey($payment->id)->lockForUpdate()->first();
            if (!$payment || !in_array($payment->status, ['initiated', 'pending'], true)) return;
            $subscription = ProviderSubscription::whereKey($payment->provider_subscription_id)->lockForUpdate()->first();
            if ($subscription && $subscription->status === 'pending') {
                $metadata = $subscription->metadata ?? [];
                if (!empty($metadata['coupon_consumed']) && !empty($metadata['coupon_code'])) {
                    $coupon = \App\Models\SubscriptionCoupon::whereRaw('upper(code) = ?', [strtoupper($metadata['coupon_code'])])->lockForUpdate()->first();
                    if ($coupon && $coupon->usage_count > 0) $coupon->decrement('usage_count');
                    $metadata['coupon_consumed'] = false;
                }
                $subscription->update(['status' => 'cancelled', 'cancelled_at' => now(), 'metadata' => array_merge($metadata, ['closed_unpaid' => true])]);
            }
            $payment->update(['status' => 'failed', 'failure_reason' => __('Paystack: :reason', ['reason' => $reason]),
                'gateway_payload' => array_merge($payment->gateway_payload ?? [], ['closed_unpaid_at' => now()->toIso8601String()])]);
        });
    }

    public function settle(array $data): ?bool
    {
        return DB::transaction(function () use ($data) {
            $payment = ProviderPaymentTransaction::where('gateway', 'paystack')->where('gateway_reference', $data['reference'] ?? '')->lockForUpdate()->first();
            if (!$payment) return null;
            if ($payment->status === 'paid') return true;
            $profile = ProviderProfile::withTrashed()->whereKey($payment->provider_profile_id)->lockForUpdate()->firstOrFail();
            $subscription = ProviderSubscription::whereKey($payment->provider_subscription_id)->lockForUpdate()->firstOrFail();
            $client = $profile->provider;
            $archived = $profile->trashed() || !$client;
            $closedUnpaid = $payment->status === 'failed' && data_get($payment->gateway_payload, 'closed_unpaid_at');
            abort_unless(in_array($payment->status, ['initiated', 'pending']) || ($archived && $payment->status === 'failed') || $closedUnpaid, 422);
            $email = data_get($payment->gateway_payload, 'expected_email') ?: ($client?->email ?? $client?->official_email);
            abort_unless(($data['status'] ?? '') === 'success' && $subscription->provider_profile_id === $profile->id && ($subscription->status === 'pending' || ($archived && $subscription->status === 'cancelled') || ($closedUnpaid && $subscription->status === 'cancelled'))
                && (int) ($data['amount'] ?? -1) === (int) round((float) $payment->amount * 100)
                && (float) $payment->amount === (float) $subscription->amount && strtoupper($data['currency'] ?? '') === $payment->currency
                && $payment->currency === $subscription->currency && $email && strtolower(data_get($data, 'customer.email', '')) === strtolower($email), 422, __('Payment does not match this account and plan.'));
            if ($archived) {
                $payment->update(['status' => 'paid', 'paid_at' => now(), 'gateway_payload' => array_merge($payment->gateway_payload ?? [], ['verified_payment' => $data, 'received_after_account_deletion' => true])]);
                return false;
            }
            $this->activate($payment, $profile, $subscription, $client, ['verified_payment' => $data], 'paystack');
            return false;
        });
    }

    /**
     * Approve the plan (starting when any current plan ends), mirror it into the original
     * specialist/facility tables, activate the account and mark the payment paid. Shared by
     * Paystack settlement and fully discounted (free) plans.
     */
    private function activate(ProviderPaymentTransaction $payment, ProviderProfile $profile, ProviderSubscription $subscription, Model $client, array $payload, string $method): void
    {
        $expires = $profile->subscriptions()->where('status', 'approved')->where('expires_at', '>', now())->max('expires_at');
        if (in_array($profile->provider_type, ['doctor', 'clinic'])) {
            $legacyExpiry = $client->subscriptions()->where('status', 'approved')->where('expires_at', '>', now())->max('expires_at');
            if ($legacyExpiry && (!$expires || $legacyExpiry > $expires)) $expires = $legacyExpiry;
        }
        $starts = $expires ? \Carbon\Carbon::parse($expires) : now();
        $subscription->update(['status' => 'approved', 'approved_at' => now(), 'starts_at' => $starts, 'expires_at' => $starts->copy()->addMonths($subscription->duration_months)]);
        if (in_array($profile->provider_type, ['doctor', 'clinic'])) {
            $type = $profile->provider_type;
            $legacy = $type === 'doctor' ? \Modules\Doctor\app\Models\DoctorSubscription::class : \Modules\Doctor\app\Models\ClinicSubscription::class;
            $legacy::updateOrCreate(['transaction_code' => $payment->gateway_reference], [$type.'_id' => $client->id, 'package_name' => $subscription->package_name, 'plan_code' => $subscription->plan_code, 'package_price' => $subscription->amount, 'plan_duration' => $subscription->duration_months,
                'entitlements' => data_get($subscription->metadata, 'entitlements', []), 'payment_method' => $method, 'status' => 'approved', 'approved_at' => $starts, 'expires_at' => $subscription->expires_at,
                'coupon_code' => data_get($subscription->metadata, 'coupon_code'), 'original_price' => data_get($subscription->metadata, 'original_price', $subscription->amount), 'discount_amount' => data_get($subscription->metadata, 'discount_amount', 0)]);
            if (!data_get($subscription->metadata, 'admin_onboarding')) $client->update(['status' => 1]);
        }
        if ($profile->status === 'pending_payment') $profile->update(['status' => 'pending_approval']);
        $payment->update(['status' => 'paid', 'paid_at' => now(), 'gateway_payload' => array_merge($payment->gateway_payload ?? [], $payload)]);
    }

    /**
     * A plan whose price a coupon brings to zero (or a free plan) needs no Paystack checkout:
     * record a zero-amount "coupon" payment and activate straight away, exactly as a paid one.
     */
    private function activateFree(Model $client, string $type, array $plan, array $quote, ?string $email): ProviderPaymentTransaction
    {
        return DB::transaction(function () use ($client, $type, $plan, $quote, $email) {
            $profile = ProviderProfile::firstOrCreate(['provider_type' => $type, 'provider_id' => $client->id], ['status' => 'pending_payment']);
            $profile = ProviderProfile::whereKey($profile->id)->lockForUpdate()->firstOrFail();
            if (in_array($type, ['school', 'supplier']) && !$client->provider_profile_id) $client->update(['provider_profile_id' => $profile->id]);
            if ($quote['coupon']) app(SubscriptionCouponService::class)->consume($quote['coupon']);
            $subscription = ProviderSubscription::create(['provider_profile_id' => $profile->id, 'plan_code' => $plan['id'], 'package_name' => $plan['name'], 'amount' => 0, 'currency' => 'KES', 'duration_months' => $plan['duration_months'], 'status' => 'pending',
                'metadata' => ['entitlements' => $plan['entitlements'], 'coupon_code' => $quote['coupon']?->code, 'coupon_consumed' => (bool) $quote['coupon'], 'original_price' => $quote['original_price'], 'discount_amount' => $quote['discount_amount']]]);
            $reference = 'FREE-'.Str::uuid();
            $payment = ProviderPaymentTransaction::create(['provider_profile_id' => $profile->id, 'provider_subscription_id' => $subscription->id, 'gateway' => 'coupon', 'gateway_reference' => $reference, 'idempotency_key' => 'coupon:'.$reference, 'amount' => 0, 'currency' => 'KES', 'status' => 'initiated', 'gateway_payload' => ['expected_email' => $email]]);
            $this->activate($payment, $profile, $subscription, $client, ['free_with_coupon' => $quote['coupon']?->code], 'coupon');
            return $payment->refresh();
        });
    }

    private function pendingAttempt(Model $client, string $type, string $code, ?string $couponCode): ?ProviderPaymentTransaction
    {
        return ProviderPaymentTransaction::with('subscription')->where('gateway', 'paystack')->whereIn('status', ['initiated', 'pending'])
            ->whereHas('providerProfile', fn ($q) => $q->whereNull('deleted_at')->where('provider_type', $type)->where('provider_id', $client->id))
            ->whereHas('subscription', fn ($q) => $q->where('status', 'pending')->where('plan_code', $code))->latest()->get()
            ->first(fn ($payment) => strtoupper(trim((string) data_get($payment->subscription->metadata, 'coupon_code'))) === strtoupper(trim((string) $couponCode)));
    }
}
