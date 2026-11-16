<?php

namespace Tests\Feature;

use App\Http\Middleware\DemoModeMiddleware;
use App\Mail\ProviderMonthlyReport;
use App\Mail\ProviderRenewalReminder;
use App\Models\ListingEvent;
use App\Models\ProviderProfile;
use App\Models\ProviderSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Doctor\app\Models\Clinic;
use Modules\Installer\app\Models\Configuration;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;
use Tests\TestCase;

class ProviderMonthlyReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-12-01 09:15:00');
        Mail::fake();
        $this->withoutMiddleware(DemoModeMiddleware::class);
        Configuration::create(['config' => 'setup_complete', 'value' => 1]);
        Cache::put('setup_complete_status', true);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function clinic(string $slug, ?string $expires, string $plan = 'premium'): Clinic
    {
        $clinic = Clinic::create(['name' => ucfirst($slug), 'slug' => $slug, 'email' => "$slug@clinic.test", 'phone' => '0700', 'password' => 'x', 'status' => 1]);
        if ($expires) $clinic->subscriptions()->create(['package_name' => ucfirst($plan), 'plan_code' => $plan, 'package_price' => 5000, 'plan_duration' => 3, 'payment_method' => 'paystack', 'status' => 'approved', 'approved_at' => now()->subMonths(2), 'expires_at' => $expires, 'transaction_code' => "T-$slug"]);
        return $clinic;
    }

    private function events(string $type, int $id, string $event, string $day, int $visitors): void
    {
        foreach (range(1, $visitors) as $v) ListingEvent::create(['provider_type' => $type, 'provider_id' => $id, 'event' => $event, 'day' => $day, 'visitor' => "v$v"]);
    }

    public function test_report_carries_last_months_numbers_and_change_and_is_sent_once(): void
    {
        $clinic = $this->clinic('busy', '2027-03-01');
        $this->events('clinic', $clinic->id, 'profile_view', '2026-11-10', 12);
        $this->events('clinic', $clinic->id, 'profile_view', '2026-10-10', 5);
        $this->events('clinic', $clinic->id, 'whatsapp_click', '2026-11-11', 3);
        $this->events('clinic', $clinic->id, 'profile_view', '2026-12-01', 99); // this month: not in November's report

        $this->artisan('providers:send-monthly-report')->assertSuccessful();
        $this->artisan('providers:send-monthly-report')->assertSuccessful();

        Mail::assertSent(ProviderMonthlyReport::class, 1);
        Mail::assertSent(ProviderMonthlyReport::class, function ($mail) {
            return $mail->hasTo('busy@clinic.test') && $mail->monthLabel === 'November 2026'
                && $mail->totals['profile_view'] === ['current' => 12, 'previous' => 5]
                && $mail->totals['whatsapp_click']['current'] === 3
                && !$mail->renewalDue && !$mail->expired
                && str_contains($mail->renewUrl, '/provider/clinic/renew-link/');
        });
        $this->assertDatabaseHas('provider_monthly_reports', ['provider_type' => 'clinic', 'provider_id' => $clinic->id, 'period' => '2026-11']);
    }

    public function test_recipients_include_recently_lapsed_but_not_long_expired_or_never_paid(): void
    {
        $this->clinic('due-soon', '2026-12-20');
        $this->clinic('lapsed', '2026-11-15');
        $this->clinic('long-gone', '2026-08-01');
        $this->clinic('never-paid', null);

        $this->artisan('providers:send-monthly-report')->assertSuccessful();

        Mail::assertSent(ProviderMonthlyReport::class, 2);
        Mail::assertSent(ProviderMonthlyReport::class, fn ($m) => $m->hasTo('due-soon@clinic.test') && $m->renewalDue && !$m->expired);
        Mail::assertSent(ProviderMonthlyReport::class, fn ($m) => $m->hasTo('lapsed@clinic.test') && $m->expired);
    }

    public function test_limit_spreads_sending_across_runs_and_suppliers_are_included(): void
    {
        foreach (['a', 'b', 'c'] as $s) $this->clinic("clinic-$s", '2027-02-01');
        $supplier = MedicalSupplier::create(['name' => 'Supplier', 'slug' => 'supplier', 'official_email' => 'sup@supplier.test', 'password' => 'x']);
        $profile = ProviderProfile::create(['provider_type' => 'supplier', 'provider_id' => $supplier->id, 'status' => 'published', 'published_at' => now()]);
        $supplier->update(['provider_profile_id' => $profile->id]);
        ProviderSubscription::create(['provider_profile_id' => $profile->id, 'plan_code' => 'executive', 'package_name' => 'Executive', 'amount' => 8000, 'currency' => 'KES', 'duration_months' => 6, 'status' => 'approved', 'starts_at' => now()->subMonth(), 'expires_at' => now()->addMonths(5)]);

        $this->artisan('providers:send-monthly-report', ['--limit' => 2]);
        Mail::assertSent(ProviderMonthlyReport::class, 2);
        $this->artisan('providers:send-monthly-report', ['--limit' => 2]);
        Mail::assertSent(ProviderMonthlyReport::class, 4);
        Mail::assertSent(ProviderMonthlyReport::class, fn ($m) => $m->hasTo('sup@supplier.test'));
        $this->assertSame(4, DB::table('provider_monthly_reports')->count());
    }

    public function test_dry_run_sends_nothing(): void
    {
        $this->clinic('quiet', '2027-02-01');
        $this->artisan('providers:send-monthly-report', ['--dry-run' => true])->expectsOutputToContain('quiet@clinic.test')->assertSuccessful();
        Mail::assertNothingSent();
        $this->assertSame(0, DB::table('provider_monthly_reports')->count());
    }

    public function test_renew_link_opens_plan_page_and_tampered_link_is_refused(): void
    {
        $clinic = $this->clinic('renewer', '2026-12-08');
        $url = app(\App\Services\ProviderRenewalLinks::class)->url('clinic', $clinic);

        $response = $this->get($url);
        $clinic->refresh();
        $this->assertNotNull($clinic->registration_token);
        $response->assertRedirect(route('clinic.subscription.complete', ['token' => $clinic->registration_token]));

        $this->get(str_replace("/renew-link/{$clinic->id}", '/renew-link/999', $url))->assertForbidden();
    }

    public function test_expiry_reminder_is_sent_once_with_renew_button_and_skipped_when_already_renewed(): void
    {
        $clinic = $this->clinic('expiring', '2026-12-08 12:00:00');
        // Payments mirror the plan into provider_subscriptions too; that used to send a second email.
        $profile = ProviderProfile::create(['provider_type' => 'clinic', 'provider_id' => $clinic->id, 'status' => 'published']);
        ProviderSubscription::create(['provider_profile_id' => $profile->id, 'plan_code' => 'premium', 'package_name' => 'Premium', 'amount' => 5000, 'currency' => 'KES', 'duration_months' => 3, 'status' => 'approved', 'starts_at' => now()->subMonth(), 'expires_at' => '2026-12-08 12:00:00']);
        $renewed = $this->clinic('renewed', '2026-12-08 12:00:00');
        $renewed->subscriptions()->create(['package_name' => 'Premium', 'plan_code' => 'premium', 'package_price' => 5000, 'plan_duration' => 3, 'payment_method' => 'paystack', 'status' => 'approved', 'approved_at' => '2026-12-08 12:00:00', 'expires_at' => '2027-03-08 12:00:00', 'transaction_code' => 'T-renewed-2']);

        $this->artisan('subscriptions:send-expiry-reminders')->assertSuccessful();

        Mail::assertSent(ProviderRenewalReminder::class, 1);
        Mail::assertSent(ProviderRenewalReminder::class, fn ($m) => $m->hasTo('expiring@clinic.test') && $m->daysLeft === 7 && str_contains($m->renewUrl, 'renew-link'));
    }
}
