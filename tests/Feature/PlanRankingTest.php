<?php

namespace Tests\Feature;

use App\Models\ProviderProfile;
use App\Models\ProviderSubscription;
use App\Support\PlanRanking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\Doctor\app\Models\Clinic;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;
use Tests\TestCase;

class PlanRankingTest extends TestCase
{
    use RefreshDatabase;

    private function clinic(string $name, ?string $plan, ?Carbon $expires = null): Clinic
    {
        $clinic = Clinic::create(['name' => $name, 'slug' => \Illuminate\Support\Str::slug($name), 'email' => \Illuminate\Support\Str::slug($name).'@example.test', 'phone' => '0700', 'password' => 'x', 'status' => 1]);
        if ($plan) $clinic->subscriptions()->create(['package_name' => ucfirst($plan), 'plan_code' => $plan, 'package_price' => 1, 'plan_duration' => 1, 'payment_method' => 'paystack', 'status' => 'approved', 'approved_at' => now()->subDay(), 'expires_at' => $expires ?? now()->addMonth(), 'transaction_code' => 'T-'.$name]);
        return $clinic;
    }

    public function test_clinics_are_ordered_by_active_plan_level(): void
    {
        $this->clinic('A Basic', 'basic');
        $this->clinic('B Executive', 'executive');
        $this->clinic('C Premium', 'premium');
        $this->clinic('D Expired Executive', 'executive', now()->subDay());
        $this->clinic('E Executive Two', 'executive');

        $rows = PlanRanking::apply(Clinic::query()->orderBy('name'), 'clinic')->get();

        $this->assertSame([3, 3, 2, 1, 0], $rows->pluck('plan_tier')->map(fn ($t) => (int) $t)->all());
        $this->assertEqualsCanonicalizing(['B Executive', 'E Executive Two'], $rows->take(2)->pluck('name')->all());
        $this->assertSame(['C Premium', 'A Basic', 'D Expired Executive'], $rows->slice(2)->pluck('name')->values()->all());

        // Pagination keeps the same order and counts every row.
        $page = PlanRanking::apply(Clinic::query(), 'clinic')->paginate(2);
        $this->assertSame(5, $page->total());
        $this->assertSame($rows->take(2)->pluck('id')->all(), $page->pluck('id')->all());
    }

    public function test_rotation_within_a_plan_level_changes_by_day_but_tiers_never_mix(): void
    {
        foreach (range(1, 6) as $i) $this->clinic("Exec $i", 'executive');
        $this->clinic('Premium', 'premium');

        $start = now();
        $orders = collect(range(0, 6))->map(function ($day) use ($start) {
            Carbon::setTestNow($start->copy()->addDays($day));
            $rows = PlanRanking::apply(Clinic::query(), 'clinic')->get();
            $this->assertSame('Premium', $rows->last()->name);
            return $rows->pluck('id')->implode(',');
        });
        Carbon::setTestNow();

        $this->assertGreaterThan(1, $orders->unique()->count(), 'Executive listings should take turns at the top.');
    }

    public function test_suppliers_rank_by_provider_subscription_plan(): void
    {
        foreach (['standard' => 'S Standard', 'executive' => 'S Executive', 'premium' => 'S Premium'] as $plan => $name) {
            $supplier = MedicalSupplier::create(['name' => $name, 'slug' => \Illuminate\Support\Str::slug($name), 'official_email' => \Illuminate\Support\Str::slug($name).'@example.test', 'password' => 'x']);
            $profile = ProviderProfile::create(['provider_type' => 'supplier', 'provider_id' => $supplier->id, 'status' => 'published', 'published_at' => now()]);
            $supplier->update(['provider_profile_id' => $profile->id]);
            ProviderSubscription::create(['provider_profile_id' => $profile->id, 'plan_code' => $plan, 'package_name' => $name, 'amount' => 1, 'currency' => 'KES', 'duration_months' => 1, 'status' => 'approved', 'approved_at' => now(), 'starts_at' => now()->subDay(), 'expires_at' => now()->addMonth()]);
        }

        $this->assertSame(['S Executive', 'S Premium', 'S Standard'], PlanRanking::apply(MedicalSupplier::query(), 'supplier')->pluck('name')->all());
    }
}
