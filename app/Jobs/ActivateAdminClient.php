<?php

namespace App\Jobs;

use App\Models\ProviderPaymentTransaction;
use App\Models\ProviderSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ActivateAdminClient implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public function __construct(public int $paymentId) {}
    public function backoff(): array { return [60, 300, 900, 1800]; }

    public function handle(): void
    {
        DB::transaction(function () {
            $payment = ProviderPaymentTransaction::findOrFail($this->paymentId);
            if ($payment->status !== 'paid') return;
            $subscription = ProviderSubscription::whereKey($payment->provider_subscription_id)->lockForUpdate()->firstOrFail();
            $metadata = $subscription->metadata ?? [];
            if ($subscription->status !== 'approved' || empty($metadata['admin_onboarding']) || !empty($metadata['login_email_sent_at'])) return;
            $profile = $payment->providerProfile;
            if (!$profile || $profile->trashed()) return;
            $client = $profile->provider;
            $client->forceFill(['email_verified_at' => now(), 'email_verified_token' => null])->save();
            if (in_array($profile->provider_type, ['doctor', 'clinic'])) {
                $client->update(['status' => 1]);
                $legacy = $profile->provider_type === 'doctor' ? \Modules\Doctor\app\Models\DoctorSubscription::class : \Modules\Doctor\app\Models\ClinicSubscription::class;
                $legacy::updateOrCreate(['transaction_code' => $payment->gateway_reference], [
                    $profile->provider_type.'_id' => $client->id, 'package_name' => $subscription->package_name,
                    'plan_code' => $subscription->plan_code, 'package_price' => $subscription->amount,
                    'plan_duration' => $subscription->duration_months, 'entitlements' => $metadata['entitlements'],
                    'payment_method' => $payment->gateway, 'status' => 'approved',
                    'approved_at' => $subscription->starts_at, 'expires_at' => $subscription->expires_at,
                ]);
            }
            $profile->update(['status' => 'approved']);
            $email = $client->email ?? $client->official_email;
            $url = route('login', ['type' => $profile->provider_type]);
            $password = Crypt::decryptString($metadata['login_secret']);
            Mail::to($email)->send(new \App\Mail\ClientAccountActivated($client->name, $email, $password, $url));
            unset($metadata['login_secret']);
            $metadata['login_email_sent_at'] = now()->toIso8601String();
            $subscription->update(['metadata' => $metadata]);
        });
    }
}
