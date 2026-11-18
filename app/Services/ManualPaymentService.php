<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\ProviderPaymentTransaction;
use App\Models\ProviderProfile;
use App\Models\ProviderSubscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Doctor\app\Models\SubscriptionPaymentSetting;
use Modules\Doctor\app\Services\SubscriptionPlanService;

/**
 * Payments that are not confirmed by Paystack:
 *  - M-Pesa paybill (e.g. the Equity Bank paybill): the provider pays from their phone, enters the
 *    M-Pesa transaction code, and an admin checks it against the bank statement and approves it;
 *  - admin activation: staff switch on a plan directly, e.g. to reactivate an account whose plan
 *    expired after payment arrived another way, or a complimentary plan.
 * Both end in ProviderPaystackService::activate(), so an approved payment behaves exactly like a
 * Paystack one: plan dates, account activation, confirmation email and PDF receipt.
 */
class ManualPaymentService
{
    public const GATEWAY_PAYBILL = 'mpesa_paybill';
    public const GATEWAY_ADMIN = 'admin';

    public function settings(): ?SubscriptionPaymentSetting
    {
        return SubscriptionPaymentSetting::query()->first();
    }

    public function paybillEnabled(): bool
    {
        $s = $this->settings();
        return (bool) ($s?->is_active && filled($s->paybill_number) && filled($s->account_number));
    }

    /** M-Pesa confirmation codes are 10 letters and digits, e.g. SKL3XYZ9AB. */
    public static function normaliseCode(string $code): string
    {
        return strtoupper(preg_replace('/\s+/', '', $code));
    }

    /** Step 1: the provider picked a plan and chose paybill. Returns the payment to show instructions for. */
    public function startPaybill(Model $client, string $type, string $code, ?string $couponCode = null): ProviderPaymentTransaction
    {
        abort_unless($this->paybillEnabled(), 503, __('M-Pesa paybill payments are not available right now. Please pay with Paystack.'));
        $plan = app(SubscriptionPlanService::class)->find($type, $code);
        if (!$plan) throw ValidationException::withMessages(['package' => __('Choose an available subscription plan.')]);
        $email = $client->email ?? $client->official_email;
        $quote = $couponCode ? app(SubscriptionCouponService::class)->quote($couponCode, $type, $code, $email, $plan['price']) : ['coupon' => null, 'price' => $plan['price'], 'original_price' => $plan['price'], 'discount_amount' => 0];
        if ($quote['price'] <= 0) return app(ProviderPaystackService::class)->activateFree($client, $type, $plan, $quote, $email);

        // Reuse an unfinished paybill attempt for the same plan and coupon instead of piling them up.
        $open = $this->openAttempts($client, $type)->first(fn ($p) => $p->subscription?->plan_code === $plan['id']
            && strtoupper((string) data_get($p->subscription->metadata, 'coupon_code')) === strtoupper((string) $quote['coupon']?->code));
        if ($open) return $open;

        $settings = $this->settings();
        return DB::transaction(function () use ($client, $type, $plan, $quote, $email, $settings) {
            $profile = ProviderProfile::firstOrCreate(['provider_type' => $type, 'provider_id' => $client->id], ['status' => 'pending_payment']);
            $profile = ProviderProfile::whereKey($profile->id)->lockForUpdate()->firstOrFail();
            if (in_array($type, ['school', 'supplier']) && !$client->provider_profile_id) $client->update(['provider_profile_id' => $profile->id]);
            if ($quote['coupon']) app(SubscriptionCouponService::class)->consume($quote['coupon']);
            $subscription = ProviderSubscription::create(['provider_profile_id' => $profile->id, 'plan_code' => $plan['id'], 'package_name' => $plan['name'], 'amount' => $quote['price'], 'currency' => 'KES', 'duration_months' => $plan['duration_months'], 'status' => 'pending',
                'metadata' => ['entitlements' => $plan['entitlements'], 'coupon_code' => $quote['coupon']?->code, 'coupon_consumed' => (bool) $quote['coupon'], 'original_price' => $quote['original_price'], 'discount_amount' => $quote['discount_amount'], 'renewal' => $profile->subscriptions()->where('status', 'approved')->exists()]]);
            $reference = 'PB-'.strtoupper(Str::random(10));
            return ProviderPaymentTransaction::create(['provider_profile_id' => $profile->id, 'provider_subscription_id' => $subscription->id, 'gateway' => self::GATEWAY_PAYBILL, 'gateway_reference' => $reference, 'idempotency_key' => 'paybill:'.$reference, 'amount' => $quote['price'], 'currency' => 'KES', 'status' => ProviderPaymentTransaction::STATUS_INITIATED,
                'gateway_payload' => ['expected_email' => $email, 'bank_name' => $settings?->bank_name, 'paybill_number' => $settings?->paybill_number, 'account_number' => $settings?->account_number]]);
        });
    }

    /** Step 2: the provider entered the M-Pesa code; the payment now waits for an admin. */
    public function submitCode(ProviderPaymentTransaction $payment, string $code, ?string $phone = null): ProviderPaymentTransaction
    {
        abort_unless($payment->gateway === self::GATEWAY_PAYBILL, 404);
        $code = self::normaliseCode($code);
        if (!preg_match('/^[A-Z0-9]{10}$/', $code)) throw ValidationException::withMessages(['mpesa_code' => __('Enter the 10-character M-Pesa code from your confirmation SMS, for example SKL3XYZ9AB.')]);
        return DB::transaction(function () use ($payment, $code, $phone) {
            $payment = ProviderPaymentTransaction::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($payment->status, [ProviderPaymentTransaction::STATUS_INITIATED, ProviderPaymentTransaction::STATUS_PENDING], true), 422, __('This payment has already been reviewed.'));
            $used = ProviderPaymentTransaction::query()->where('gateway', self::GATEWAY_PAYBILL)->whereKeyNot($payment->id)
                ->whereIn('status', [ProviderPaymentTransaction::STATUS_PENDING, ProviderPaymentTransaction::STATUS_PAID])
                ->where('gateway_payload->mpesa_code', $code)->exists();
            if ($used) throw ValidationException::withMessages(['mpesa_code' => __('This M-Pesa code has already been submitted. Contact MedFind if you think this is a mistake.')]);
            $payment->update(['status' => ProviderPaymentTransaction::STATUS_PENDING, 'failure_reason' => null, 'gateway_payload' => array_merge($payment->gateway_payload ?? [], array_filter([
                'mpesa_code' => $code, 'payer_phone' => $phone ? trim($phone) : null, 'submitted_at' => now()->toIso8601String(),
            ]))]);
            return $payment;
        });
    }

    /** Admin confirmed the code on the bank statement: activate exactly like a Paystack payment. */
    public function approve(ProviderPaymentTransaction $payment, Admin $admin, ?string $note = null): void
    {
        DB::transaction(function () use ($payment, $admin, $note) {
            $payment = ProviderPaymentTransaction::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            abort_unless($payment->gateway === self::GATEWAY_PAYBILL && $payment->status === ProviderPaymentTransaction::STATUS_PENDING, 422, __('Only paybill payments waiting for review can be approved.'));
            $profile = ProviderProfile::whereKey($payment->provider_profile_id)->lockForUpdate()->firstOrFail();
            $subscription = ProviderSubscription::whereKey($payment->provider_subscription_id)->lockForUpdate()->firstOrFail();
            abort_unless($profile->provider, 422, __('This account no longer exists.'));
            app(ProviderPaystackService::class)->activate($payment, $profile, $subscription, $profile->provider,
                ['approved_by' => $admin->id, 'approved_by_name' => $admin->name, 'approved_at' => now()->toIso8601String(), 'approval_note' => $note], self::GATEWAY_PAYBILL);
        });
        PublicDirectory::forget();
    }

    /** Admin could not find the payment: the provider is told why and can submit a corrected code. */
    public function reject(ProviderPaymentTransaction $payment, Admin $admin, string $reason): void
    {
        DB::transaction(function () use ($payment, $admin, $reason) {
            $payment = ProviderPaymentTransaction::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            abort_unless($payment->gateway === self::GATEWAY_PAYBILL && $payment->status === ProviderPaymentTransaction::STATUS_PENDING, 422, __('Only paybill payments waiting for review can be rejected.'));
            // Back to "initiated": the provider sees the reason on the same page and can enter a corrected code.
            $payment->update(['status' => ProviderPaymentTransaction::STATUS_INITIATED, 'failure_reason' => $reason, 'gateway_payload' => array_merge($payment->gateway_payload ?? [], [
                'rejected_code' => data_get($payment->gateway_payload, 'mpesa_code'), 'mpesa_code' => null, 'rejected_by' => $admin->id, 'rejected_at' => now()->toIso8601String(),
            ])]);
        });
        $payment->refresh();
        $client = $payment->providerProfile?->provider;
        $email = $client ? ProviderRenewalLinks::email($client) : null;
        if ($email) {
            try {
                \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\PaybillPaymentRejected($client->name, $reason, self::paymentUrl($payment)));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Paybill rejection email failed', ['payment' => $payment->id, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Staff switch a plan on directly, e.g. an account whose plan expired, or payment received another way.
     * The new plan starts when any current plan ends, or now if it has expired.
     */
    public function adminActivate(Model $client, string $type, string $planCode, float $amount, ?string $reference, ?string $note, Admin $admin): ProviderPaymentTransaction
    {
        $plan = app(SubscriptionPlanService::class)->find($type, $planCode);
        if (!$plan) throw ValidationException::withMessages(['package' => __('Choose an available subscription plan.')]);
        $payment = DB::transaction(function () use ($client, $type, $plan, $amount, $reference, $note, $admin) {
            $profile = ProviderProfile::firstOrCreate(['provider_type' => $type, 'provider_id' => $client->id], ['status' => 'pending_payment']);
            $profile = ProviderProfile::whereKey($profile->id)->lockForUpdate()->firstOrFail();
            if (in_array($type, ['school', 'supplier']) && !$client->provider_profile_id) $client->update(['provider_profile_id' => $profile->id]);
            $renewal = $profile->subscriptions()->where('status', 'approved')->exists();
            $subscription = ProviderSubscription::create(['provider_profile_id' => $profile->id, 'plan_code' => $plan['id'], 'package_name' => $plan['name'], 'amount' => $amount, 'currency' => 'KES', 'duration_months' => $plan['duration_months'], 'status' => 'pending',
                'metadata' => ['entitlements' => $plan['entitlements'], 'original_price' => $plan['price'], 'discount_amount' => max(0, $plan['price'] - $amount), 'renewal' => $renewal, 'activated_by_admin' => $admin->id]]);
            $ref = 'ADM-'.strtoupper(Str::random(10));
            $payment = ProviderPaymentTransaction::create(['provider_profile_id' => $profile->id, 'provider_subscription_id' => $subscription->id, 'gateway' => self::GATEWAY_ADMIN, 'gateway_reference' => $ref, 'idempotency_key' => 'admin:'.$ref, 'amount' => $amount, 'currency' => 'KES', 'status' => ProviderPaymentTransaction::STATUS_INITIATED,
                'gateway_payload' => ['expected_email' => $client->email ?? $client->official_email]]);
            app(ProviderPaystackService::class)->activate($payment, $profile, $subscription, $client,
                array_filter(['activated_by' => $admin->id, 'activated_by_name' => $admin->name, 'external_reference' => $reference, 'activation_note' => $note]), self::GATEWAY_ADMIN);
            return $payment->refresh();
        });
        PublicDirectory::forget();
        return $payment;
    }

    /** Unfinished paybill payments of one account (waiting for a code, or for review). */
    public function openAttempts(Model $client, string $type)
    {
        return ProviderPaymentTransaction::with('subscription')->where('gateway', self::GATEWAY_PAYBILL)
            ->whereIn('status', [ProviderPaymentTransaction::STATUS_INITIATED, ProviderPaymentTransaction::STATUS_PENDING])
            ->whereHas('providerProfile', fn ($q) => $q->where('provider_type', $type)->where('provider_id', $client->getKey()))
            ->latest()->get();
    }

    /** The instructions page; signed so it works from email without signing in. */
    public static function paymentUrl(ProviderPaymentTransaction $payment): string
    {
        return \Illuminate\Support\Facades\URL::signedRoute('provider.paybill.show', ['transaction' => $payment->id]);
    }
}
