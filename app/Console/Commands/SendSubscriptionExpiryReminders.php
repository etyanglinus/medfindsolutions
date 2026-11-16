<?php

namespace App\Console\Commands;

use App\Models\ProviderSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Doctor\app\Models\ClinicSubscription;
use Modules\Doctor\app\Models\DoctorSubscription;

class SendSubscriptionExpiryReminders extends Command
{
    protected $signature = 'subscriptions:send-expiry-reminders';
    protected $description = 'Email providers whose subscriptions expire in 7 days';

    public function handle(): int
    {
        // ── Legacy doctor subscriptions ──────────────────────────────
        DoctorSubscription::with('doctor')
            ->where('status', 'approved')
            ->whereDate('expires_at', today()->addDays(7))
            ->get()
            ->each(function ($subscription) {
                $email = $subscription->doctor?->email ?? null;
                $name  = $subscription->doctor?->name  ?? null;
                $this->send($email, $name, 'subscriptions:send-expiry-reminders (doctor)');
            });

        // ── Legacy clinic subscriptions ───────────────────────────────
        ClinicSubscription::with('clinic')
            ->where('status', 'approved')
            ->whereDate('expires_at', today()->addDays(7))
            ->get()
            ->each(function ($subscription) {
                $email = $subscription->clinic?->email ?? $subscription->clinic?->official_email ?? null;
                $name  = $subscription->clinic?->name  ?? null;
                $this->send($email, $name, 'subscriptions:send-expiry-reminders (clinic)');
            });

        // ── School / supplier ProviderSubscriptions ───────────────────
        ProviderSubscription::with('providerProfile')
            ->where('status', ProviderSubscription::STATUS_APPROVED)
            ->whereDate('expires_at', today()->addDays(7))
            ->get()
            ->each(function ($subscription) {
                $profile  = $subscription->providerProfile;
                if (! $profile) return;

                // Resolve the underlying entity so we can get the email address
                $provider = $profile->provider;
                if (! $provider) return;

                $email = $provider->official_email ?? null;
                $name  = $provider->name ?? null;
                $this->send($email, $name, 'subscriptions:send-expiry-reminders (' . $profile->provider_type . ')');
            });

        $this->info('Subscription expiry reminders processed.');
        return self::SUCCESS;
    }

    private function send(?string $email, ?string $name, string $context): void
    {
        if (! $email) return;

        try {
            Mail::raw(
                __('Your MedFind subscription expires in 7 days. Sign in to renew and keep your profile visible.'),
                fn ($mail) => $mail->to($email, $name)->subject(__('MedFind subscription expiry reminder'))
            );
        } catch (\Throwable $e) {
            Log::warning("Subscription reminder could not be sent [{$context}] to {$email}: " . $e->getMessage());
        }
    }
}
