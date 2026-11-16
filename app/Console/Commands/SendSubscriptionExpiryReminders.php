<?php

namespace App\Console\Commands;

use App\Mail\ProviderRenewalReminder;
use App\Models\ProviderSubscription;
use App\Services\ProviderRenewalLinks;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Doctor\app\Models\ClinicSubscription;
use Modules\Doctor\app\Models\DoctorSubscription;

/**
 * Daily: emails providers whose plan ends in 7 days or tomorrow, with a "Renew now" button that
 * opens their plan page without a login. Specialists and facilities are read from their own plan
 * tables only (their rows are mirrored in provider_subscriptions, which used to email them twice).
 */
class SendSubscriptionExpiryReminders extends Command
{
    protected $signature = 'subscriptions:send-expiry-reminders';
    protected $description = 'Email providers whose subscriptions expire in 7 days or tomorrow';

    public const DAYS_BEFORE = [7, 1];

    public function handle(ProviderRenewalLinks $links): int
    {
        $sent = 0;
        foreach (self::DAYS_BEFORE as $days) {
            $day = today()->addDays($days);

            DoctorSubscription::with('doctor')->where('status', 'approved')->whereDate('expires_at', $day)->get()
                ->each(function ($s) use ($links, $days, &$sent) { $sent += $this->remind('doctor', $s->doctor, $s->package_name, $s->expires_at, $days, $links); });

            ClinicSubscription::with('clinic')->where('status', 'approved')->whereDate('expires_at', $day)->get()
                ->each(function ($s) use ($links, $days, &$sent) { $sent += $this->remind('clinic', $s->clinic, $s->package_name, $s->expires_at, $days, $links); });

            ProviderSubscription::with('providerProfile')->where('status', ProviderSubscription::STATUS_APPROVED)->whereDate('expires_at', $day)
                ->whereHas('providerProfile', fn ($q) => $q->whereIn('provider_type', ['school', 'supplier']))->get()
                ->each(function ($s) use ($links, $days, &$sent) {
                    $profile = $s->providerProfile;
                    $sent += $this->remind($profile->provider_type, $profile->provider, $s->package_name, $s->expires_at, $days, $links);
                });
        }

        $this->info("Subscription expiry reminders sent: {$sent}.");
        return self::SUCCESS;
    }

    private function remind(string $type, ?Model $client, ?string $plan, $expiresAt, int $days, ProviderRenewalLinks $links): int
    {
        $email = $client ? ProviderRenewalLinks::email($client) : null;
        if (!$email) return 0;
        // A renewal already paid for (a later plan) means no reminder is needed.
        if ($this->hasLaterPlan($type, $client, $expiresAt)) return 0;

        try {
            Mail::to($email, $client->name)->send(new ProviderRenewalReminder(
                $client->name, (string) ($plan ?: 'MedFind'), $expiresAt->format('d M Y'), $days, $links->url($type, $client),
            ));
            return 1;
        } catch (\Throwable $e) {
            Log::warning("Expiry reminder could not be sent to {$type} #{$client->id}: ".$e->getMessage());
            return 0;
        }
    }

    private function hasLaterPlan(string $type, Model $client, $expiresAt): bool
    {
        $query = in_array($type, ['doctor', 'clinic'], true)
            ? $client->subscriptions()
            : ProviderSubscription::whereHas('providerProfile', fn ($q) => $q->where('provider_type', $type)->where('provider_id', $client->id));
        return $query->where('status', 'approved')->where('expires_at', '>', $expiresAt)->exists();
    }
}
