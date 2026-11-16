<?php

namespace App\Services;

use App\Models\ListingEvent;
use App\Models\ProviderProfile;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Modules\Doctor\app\Models\Clinic;
use Modules\Doctor\app\Models\Doctor;
use Modules\Doctor\app\Models\ProviderInquiry;

/**
 * Builds the monthly "your listing performance" report each provider receives: last month's search
 * appearances, profile views, calls, WhatsApp and website clicks and enquiries, compared with the
 * month before, plus their plan status and a "Renew now" link.
 */
class ProviderMonthlyReportService
{
    /** Providers stay on the mailing list for this many days after their plan ends (win-back). */
    public const LAPSED_DAYS = 60;

    public const METRICS = [
        'search_appearance' => 'Appeared in search results',
        'profile_view' => 'Profile views',
        'phone_click' => 'Phone calls started',
        'whatsapp_click' => 'WhatsApp chats started',
        'website_click' => 'Website visits',
        'inquiries' => 'Enquiries received',
    ];

    /**
     * Everyone who should get a report: an active plan, or one that ended in the last 60 days.
     *
     * @return \Generator<array{type: string, client: Model, plan: ?string, expires_at: ?\Carbon\CarbonInterface}>
     */
    public function recipients(): \Generator
    {
        $cutoff = now()->subDays(self::LAPSED_DAYS);

        foreach (['doctor' => Doctor::class, 'clinic' => Clinic::class] as $type => $model) {
            $query = $model::query()->where('status', 1)
                ->whereHas('subscriptions', fn ($q) => $q->where('status', 'approved')->where(fn ($w) => $w->whereNull('expires_at')->orWhere('expires_at', '>', $cutoff)))
                ->with(['subscriptions' => fn ($q) => $q->where('status', 'approved')]);
            foreach ($query->lazyById(200) as $client) {
                $plan = $client->subscriptions->sortByDesc(fn ($s) => $s->expires_at?->timestamp ?? PHP_INT_MAX)->first();
                yield ['type' => $type, 'client' => $client, 'plan' => $plan?->package_name, 'expires_at' => $plan?->expires_at];
            }
        }

        $profiles = ProviderProfile::query()->whereIn('provider_type', ['school', 'supplier'])
            ->whereHas('subscriptions', fn ($q) => $q->where('status', 'approved')->where(fn ($w) => $w->whereNull('expires_at')->orWhere('expires_at', '>', $cutoff)))
            ->with(['subscriptions' => fn ($q) => $q->where('status', 'approved')]);
        foreach ($profiles->lazyById(200) as $profile) {
            $client = $profile->provider;
            if (!$client) continue;
            $plan = $profile->subscriptions->sortByDesc(fn ($s) => $s->expires_at?->timestamp ?? PHP_INT_MAX)->first();
            yield ['type' => $profile->provider_type, 'client' => $client, 'plan' => $plan?->package_name, 'expires_at' => $plan?->expires_at];
        }
    }

    /** @return array<string, array{current: int, previous: int}> */
    public function totals(string $type, Model $client, string $period): array
    {
        $month = CarbonImmutable::createFromFormat('!Y-m', $period);
        $current = $this->countsBetween($type, $client, $month->startOfMonth(), $month->endOfMonth());
        $previous = $this->countsBetween($type, $client, $month->subMonthNoOverflow()->startOfMonth(), $month->subMonthNoOverflow()->endOfMonth());

        $out = [];
        foreach (array_keys(self::METRICS) as $metric) {
            $out[$metric] = ['current' => $current[$metric] ?? 0, 'previous' => $previous[$metric] ?? 0];
        }
        return $out;
    }

    private function countsBetween(string $type, Model $client, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $counts = ListingEvent::query()->where('provider_type', $type)->where('provider_id', $client->id)
            ->whereBetween('day', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('event, COUNT(*) as total')->groupBy('event')->pluck('total', 'event')
            ->map(fn ($n) => (int) $n)->all();

        $inquiries = match ($type) {
            'doctor', 'clinic' => ProviderInquiry::where('provider_type', $type)->where('provider_id', $client->id),
            'school' => $client->enquiries(),
            'supplier' => $client->quotationRequests(),
        };
        $counts['inquiries'] = (int) $inquiries->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])->count();

        return $counts;
    }
}
