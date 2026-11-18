<?php

namespace App\Console\Commands;

use App\Mail\ProfileCompletionReminder;
use App\Models\ProviderProfile;
use App\Services\ProviderOnboardingService;
use App\Services\ProviderRenewalLinks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Doctor\app\Models\Clinic;
use Modules\Doctor\app\Models\Doctor;

/**
 * "Pay and forget": providers with a paid, current plan whose profile is still incomplete get an
 * email listing exactly what is missing, 1, 3, 7 and 14 days after their plan started. Each stage
 * is sent once; nothing is sent once the profile is complete.
 */
class SendProfileCompletionReminders extends Command
{
    protected $signature = 'providers:profile-reminders {--dry-run}';
    protected $description = 'Remind paid providers to finish their profile so their listing goes live';

    public const STAGES = [1, 3, 7, 14];

    public function handle(ProviderOnboardingService $onboarding): int
    {
        $sent = 0;
        foreach ($this->candidates() as [$type, $client, $planName, $startedAt]) {
            $days = (int) $startedAt->copy()->startOfDay()->diffInDays(now()->startOfDay());
            $stage = collect(self::STAGES)->filter(fn ($s) => $days >= $s)->last();
            if (!$stage) continue;
            $status = $onboarding->status($client, $type);
            if ($status['complete']) continue;
            if (DB::table('profile_completion_reminders')->where(['provider_type' => $type, 'provider_id' => $client->id])->where('stage', '>=', $stage)->exists()) continue;
            $email = ProviderRenewalLinks::email($client);
            if (!$email) continue;
            $missing = $status['missing'];
            if (!$status['photo']) $missing[] = $type === 'doctor' ? __('Profile photo') : __('Logo or photo');
            if (!$status['document']) $missing[] = __('Licence or registration document');
            if ($this->option('dry-run')) { $this->line("$type #{$client->id} $email day $days: ".implode(', ', $missing)); $sent++; continue; }
            try {
                Mail::to($email)->send(new ProfileCompletionReminder($client->name, (string) $planName, array_values(array_unique($missing)), route('login', ['type' => $type]), $days));
                DB::table('profile_completion_reminders')->insertOrIgnore(['provider_type' => $type, 'provider_id' => $client->id, 'stage' => $stage, 'sent_at' => now()]);
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Profile reminder failed', ['type' => $type, 'id' => $client->id, 'error' => $e->getMessage()]);
            }
        }
        $this->info(($this->option('dry-run') ? 'Would send ' : 'Sent ')."$sent profile reminders.");
        return self::SUCCESS;
    }

    /** @return \Generator<array{0: string, 1: \Illuminate\Database\Eloquent\Model, 2: ?string, 3: \Carbon\CarbonInterface}> */
    private function candidates(): \Generator
    {
        foreach (['doctor' => Doctor::class, 'clinic' => Clinic::class] as $type => $model) {
            $query = $model::query()->where('status', 1)
                ->whereHas('subscriptions', fn ($q) => $q->where('status', 'approved')->where(fn ($w) => $w->whereNull('expires_at')->orWhere('expires_at', '>', now())))
                ->with(['subscriptions' => fn ($q) => $q->where('status', 'approved')]);
            foreach ($query->lazyById(200) as $client) {
                $plan = $client->subscriptions->sortByDesc('approved_at')->first();
                if ($plan?->approved_at) yield [$type, $client, $plan->package_name, $plan->approved_at];
            }
        }
        $profiles = ProviderProfile::query()->whereIn('provider_type', ['school', 'supplier'])->where('status', '!=', ProviderProfile::STATUS_SUSPENDED)
            ->whereHas('subscriptions', fn ($q) => $q->where('status', 'approved')->where(fn ($w) => $w->whereNull('expires_at')->orWhere('expires_at', '>', now())))
            ->with(['subscriptions' => fn ($q) => $q->where('status', 'approved')]);
        foreach ($profiles->lazyById(200) as $profile) {
            $client = $profile->provider;
            $plan = $profile->subscriptions->sortByDesc(fn ($s) => $s->starts_at ?? $s->approved_at)->first();
            $start = $plan?->starts_at ?? $plan?->approved_at;
            if ($client && $start) yield [$profile->provider_type, $client, $plan->package_name, $start];
        }
    }
}
