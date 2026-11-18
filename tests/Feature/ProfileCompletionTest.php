<?php

namespace Tests\Feature;

use App\Mail\ProfileCompletionReminder;
use App\Services\ProfileRequirements;
use App\Services\PublicDirectory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Doctor\app\Models\Clinic;
use Tests\TestCase;

/** Paying is not enough: the profile must be complete before the listing shows, and forgetful payers are reminded. */
class ProfileCompletionTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\CompletesProviderProfiles;

    private function paidClinic(int $daysAgo, array $entitlements = ['search_visibility' => true, 'whatsapp' => true, 'operating_hours' => true]): Clinic
    {
        $clinic = Clinic::create(['name' => 'Forgetful Clinic', 'slug' => 'forgetful-'.$daysAgo, 'email' => "f$daysAgo@example.test", 'phone' => '0700', 'password' => 'x', 'status' => 1]);
        $clinic->subscriptions()->create(['package_name' => 'Premium', 'plan_code' => 'premium', 'package_price' => 3000, 'plan_duration' => 3, 'payment_method' => 'paystack', 'status' => 'approved',
            'approved_at' => now()->subDays($daysAgo), 'expires_at' => now()->addMonths(2), 'transaction_code' => "T-$daysAgo", 'entitlements' => $entitlements]);
        return $clinic;
    }

    public function test_incomplete_paid_listing_is_hidden_and_plan_sections_are_required(): void
    {
        $clinic = $this->paidClinic(0);
        $this->assertFalse(app(PublicDirectory::class)->status('clinic', $clinic)['listed'], 'No photo or description: hidden.');
        $this->assertContains('Profile not completed (logo or description missing)', app(PublicDirectory::class)->status('clinic', $clinic)['reasons']);

        $missing = app(ProfileRequirements::class)->missing('clinic', $clinic);
        $this->assertContains('WhatsApp number', $missing);
        $this->assertContains('Opening hours', $missing);
        $this->assertNotContains('Insurance accepted', $missing, 'Not in this plan, so not required.');

        $this->completeClinic($clinic);
        $this->assertTrue(app(PublicDirectory::class)->status('clinic', $clinic->fresh())['listed']);
    }

    public function test_pay_and_forget_reminders_go_once_per_stage_and_stop_when_complete(): void
    {
        Mail::fake();
        $clinic = $this->paidClinic(3);
        $this->artisan('providers:profile-reminders')->assertSuccessful();
        $this->artisan('providers:profile-reminders')->assertSuccessful();
        Mail::assertSent(ProfileCompletionReminder::class, 1);
        Mail::assertSent(ProfileCompletionReminder::class, fn ($m) => $m->hasTo('f3@example.test') && in_array('WhatsApp number', $m->missing, true));

        $this->travel(4)->days(); // day 7: next stage
        $this->artisan('providers:profile-reminders');
        Mail::assertSent(ProfileCompletionReminder::class, 2);

        // Complete everything: no more reminders.
        $this->completeClinic($clinic);
        $clinic->forceFill(['whatsapp' => '0711', 'operating_hours' => 'Mon–Fri 8–5', 'specialty' => 'Dental', 'licensing_body' => 'KMPDC', 'address' => 'Nairobi', 'educations' => 'BDS', 'experience' => '8 years', 'qualifications' => 'KMPDC'])->save();
        \Modules\Doctor\app\Models\ProviderDocument::create(['provider_type' => 'clinic', 'provider_id' => $clinic->id, 'licensing_body' => 'KMPDC', 'registration_number' => 'R', 'file_path' => 'x.pdf', 'status' => 'pending']);
        $this->travel(8)->days(); // day 15
        $this->artisan('providers:profile-reminders');
        Mail::assertSent(ProfileCompletionReminder::class, 2);
    }

    public function test_every_sign_up_form_asks_for_a_photo(): void
    {
        \Illuminate\Support\Facades\Cache::put('setting', new class extends \stdClass {
            public $app_name = 'Medfind Test'; public $recaptcha_status = 'inactive'; public $version = '1.0'; public $cookie_status = 'inactive'; public $doctor_can_register = 1;
            public function __get($key) { return ''; }
            public function __isset($key) { return false; }
        });
        \Modules\Installer\app\Models\Configuration::create(['config' => 'setup_complete', 'value' => 1]);
        $this->withoutMiddleware([\App\Http\Middleware\DemoModeMiddleware::class, \App\Http\Middleware\SetLocaleMiddleware::class, \App\Http\Middleware\MaintenanceModeMiddleware::class, \App\Http\Middleware\TrackSiteVisit::class]);
        $html = $this->get(route('provider.register'))->assertOk()->getContent();
        $forms = [];
        preg_match_all('/<form\b(.*?)>(.*?)<\/form>/s', $html, $m, PREG_SET_ORDER);
        foreach ($m as [$all, $attrs, $body]) {
            if (!preg_match('/action="([^"]+)"/', $attrs, $a)) continue;
            preg_match_all('/type="file"\s+name="([a-z_]+)"/', $body, $f);
            $forms[parse_url($a[1], PHP_URL_PATH)] = ['files' => $f[1], 'multipart' => str_contains($attrs, 'multipart/form-data')];
        }
        foreach (['/doctor/register' => 'image', '/clinic/register' => 'logo', '/partner/school/register' => 'logo', '/partner/supplier/register' => 'logo'] as $path => $field) {
            $this->assertContains($field, $forms[$path]['files'] ?? [], "$path asks for a photo");
            $this->assertTrue($forms[$path]['multipart'] ?? false, "$path can upload files");
        }
    }
}
