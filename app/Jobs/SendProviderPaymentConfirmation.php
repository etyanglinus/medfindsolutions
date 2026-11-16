<?php

namespace App\Jobs;

use App\Mail\ProviderPaymentConfirmed;
use App\Models\ProviderPaymentTransaction;
use App\Models\ProviderSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Emails a self-registered provider (specialist, facility, school, supplier) once Paystack confirms
 * their payment, with a signed link that confirms their email address and opens the login page.
 * Safe to run repeatedly: it sends at most once per subscription. Failures are logged, not thrown,
 * so a mail outage never breaks the payment callback; `paystack:reconcile` retries unsent emails.
 */
class SendProviderPaymentConfirmation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $paymentId) {}

    public static function shouldSend(ProviderPaymentTransaction $payment): bool
    {
        $metadata = $payment->subscription?->metadata ?? [];
        return $payment->status === 'paid'
            && empty($metadata['admin_onboarding'])            // admin-created clients get ClientAccountActivated instead
            && empty($metadata['confirmation_email_sent_at'])
            && !data_get($payment->gateway_payload, 'received_after_account_deletion');
    }

    public function handle(): void
    {
        try {
            DB::transaction(function () {
                $payment = ProviderPaymentTransaction::with('providerProfile')->findOrFail($this->paymentId);
                $subscription = ProviderSubscription::whereKey($payment->provider_subscription_id)->lockForUpdate()->first();
                if (!$subscription) return;
                $payment->setRelation('subscription', $subscription);
                if (!self::shouldSend($payment) || $subscription->status !== 'approved') return;
                $profile = $payment->providerProfile;
                $client = $profile?->provider;
                if (!$profile || $profile->trashed() || !$client) return;

                $email = $client->email ?? $client->official_email;
                if (!$email) return;
                $accessUrl = URL::temporarySignedRoute('provider.payment-access', now()->addDays(7), ['type' => $profile->provider_type, 'id' => $client->id]);

                Mail::to($email)->send(new ProviderPaymentConfirmed(
                    $client->name, $email, (string) $subscription->package_name,
                    $payment->currency.' '.number_format((float) $payment->amount, 2),
                    $subscription->expires_at?->format('d M Y'), $payment->gateway_reference, $accessUrl,
                ));

                $subscription->update(['metadata' => array_merge($subscription->metadata ?? [], ['confirmation_email_sent_at' => now()->toIso8601String()])]);
            });
        } catch (\Throwable $e) {
            Log::error('Payment confirmation email failed', ['payment' => $this->paymentId, 'error' => $e->getMessage()]);
        }
    }
}
