<?php

namespace Modules\Doctor\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Doctor\app\Services\SubscriptionPlanService;
use Modules\Doctor\app\Models\SubscriptionPackage;

class SubscriptionPackageSeeder extends Seeder
{
    public function run(): void
    {
        $plans = app(SubscriptionPlanService::class);
        foreach (['doctor', 'clinic', 'school', 'supplier'] as $provider) {
            foreach ($plans->defaults($provider) as $plan) {
                SubscriptionPackage::firstOrCreate(
                    ['provider_type' => $provider, 'code' => $plan['id']],
                    [
                        'name' => $plan['name'], 'tagline' => $plan['tagline'], 'price' => $plan['price'],
                        'duration_months' => $plan['duration_months'], 'features' => $plan['features'],
                        'entitlements' => $plan['entitlements'], 'is_active' => true,
                    ]
                );
            }
        }
    }
}
