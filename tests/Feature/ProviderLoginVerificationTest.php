<?php

namespace Tests\Feature;

use App\Http\Middleware\DemoModeMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Doctor\app\Models\Clinic;
use Modules\Doctor\app\Models\Doctor;
use Modules\Installer\app\Models\Configuration;
use Tests\TestCase;

class ProviderLoginVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['app.app_mode' => 'LIVE']);
        $this->withoutMiddleware(DemoModeMiddleware::class);
        Configuration::create(['config' => 'setup_complete', 'value' => 1]);
        Cache::put('setup_complete_status', true);
        Cache::forever('setting', (object) ['maintenance_mode' => '0', 'recaptcha_status' => 'inactive', 'timezone' => 'Africa/Nairobi', 'app_name' => 'MedFind']);
    }

    private function doctor(?string $verifiedAt): Doctor
    {
        $department = DB::table('departments')->insertGetId(['slug' => 'gp', 'status' => 1]);
        $location = DB::table('locations')->insertGetId(['status' => 1]);
        $doctor = Doctor::create(['department_id' => $department, 'location_id' => $location, 'name' => 'Dr Mail', 'slug' => 'dr-mail', 'email' => 'dr@example.test', 'phone' => '0700', 'password' => bcrypt('secret-pass'), 'status' => 1, 'email_verified_at' => $verifiedAt]);
        $doctor->subscriptions()->create(['package_name' => 'Basic', 'plan_code' => 'basic', 'package_price' => 1000, 'plan_duration' => 1, 'payment_method' => 'paystack', 'status' => 'approved', 'approved_at' => now()->subDay(), 'expires_at' => now()->addMonth(), 'transaction_code' => 'T1']);
        return $doctor;
    }

    public function test_unverified_specialist_is_sent_a_link_instead_of_being_signed_in(): void
    {
        $doctor = $this->doctor(null);

        $this->post(route('doctor.login'), ['email' => 'dr@example.test', 'password' => 'secret-pass'])
            ->assertRedirect(route('login', ['type' => 'doctor']))->assertSessionHas('message', fn ($m) => str_contains($m, 'just sent'));
        $this->assertGuest('doctor');
        $this->assertNotNull($token = $doctor->fresh()->email_verified_token);

        // A second attempt within 10 minutes does not send another email or change the link.
        $this->post(route('doctor.login'), ['email' => 'dr@example.test', 'password' => 'secret-pass'])->assertSessionHas('message', fn ($m) => str_contains($m, 'a few minutes ago'));
        $this->assertSame($token, $doctor->fresh()->email_verified_token);

        // Wrong password: no hint about verification and no email.
        $this->post(route('doctor.login'), ['email' => 'dr@example.test', 'password' => 'wrong'])->assertSessionHas('message', 'Invalid Credentials');

        // Following the link verifies and sends an active specialist to sign in.
        $this->get(route('doctor.verification', $token))->assertRedirect(route('login', ['type' => 'doctor']));
        $this->assertNotNull($doctor->fresh()->email_verified_at);
    }

    public function test_verified_specialist_signs_in(): void
    {
        $this->doctor(now()->toDateTimeString());
        $this->post(route('doctor.login'), ['email' => 'dr@example.test', 'password' => 'secret-pass']);
        $this->assertAuthenticated('doctor');
    }

    public function test_unverified_facility_is_not_signed_in(): void
    {
        $clinic = Clinic::create(['name' => 'Clinic', 'slug' => 'clinic', 'email' => 'clinic@example.test', 'phone' => '0700', 'password' => bcrypt('secret-pass'), 'status' => 1]);
        $clinic->subscriptions()->create(['package_name' => 'Basic', 'plan_code' => 'basic', 'package_price' => 3000, 'plan_duration' => 1, 'payment_method' => 'paystack', 'status' => 'approved', 'approved_at' => now()->subDay(), 'expires_at' => now()->addMonth(), 'transaction_code' => 'T2']);

        $this->post(route('clinic.login'), ['email' => 'clinic@example.test', 'password' => 'secret-pass'])
            ->assertRedirect(route('login', ['type' => 'clinic']));
        $this->assertGuest('clinic');
        $this->assertNotNull($clinic->fresh()->email_verified_token);
    }
}
