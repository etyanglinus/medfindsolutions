<?php

namespace App\Services;

use App\Models\ProviderProfile;
use App\Models\ProviderSubscription;
use Carbon\CarbonInterface;
use Modules\Doctor\app\Services\SubscriptionPlanService;

class PartnerPortalService
{
    public function subscription(ProviderProfile $profile): ?ProviderSubscription
    {
        return $profile->subscriptions()
            ->where('status', ProviderSubscription::STATUS_APPROVED)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latest('starts_at')->latest('id')->first();
    }

    public function entitlements(ProviderProfile $profile): array
    {
        $subscription = $this->subscription($profile);
        if (! $subscription) return [];

        $entitlements = data_get($subscription->metadata, 'entitlements');
        if (! is_array($entitlements) && $subscription->package) $entitlements = $subscription->package->entitlements;
        if (! is_array($entitlements)) {
            $plan = app(SubscriptionPlanService::class)->find($profile->provider_type, $subscription->plan_code);
            $entitlements = $plan['entitlements'] ?? [];
        }
        $supported = app(SubscriptionPlanService::class)->supportedEntitlements($profile->provider_type, $entitlements ?? []);
        return $supported;
    }

    public function allows(ProviderProfile $profile, string $feature): bool
    {
        return (bool) data_get($this->entitlements($profile), $feature, false);
    }

    public function limit(ProviderProfile $profile, string $key): ?int
    {
        $limit = data_get($this->entitlements($profile), $key);
        return is_numeric($limit) ? (int) $limit : null;
    }
}
