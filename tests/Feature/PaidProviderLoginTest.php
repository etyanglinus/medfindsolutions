<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Services\ManualPaymentService;
use App\Services\ProviderPaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Doctor\app\Models\SubscriptionPaymentSetting;
use Tests\TestCase;

/**
 * A provider who has just paid (Paystack, M-Pesa paybill approved by staff, or a plan switched on
 * by an admin) must be able to sign in and reach every page of their portal without a server error.
 */
class PaidProviderLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Cache::put('setting', new class extends \stdClass {
            public $app_name = 'Medfind Test'; public $recaptcha_status = 'inactive'; public $version = '1.0'; public $cookie_status = 'inactive';
            public $logo = 'logo.png'; public $favicon = 'favicon.png'; public $default_avatar = 'avatar.png';
            public function __get($key) { return ''; }
            public function __isset($key) { return false; }
        });
        \Modules\Installer\app\Models\Configuration::create(['config' => 'setup_complete', 'value' => 1]);
        $this->withoutMiddleware([\App\Http\Middleware\DemoModeMiddleware::class, \App\Http\Middleware\SetLocaleMiddleware::class, \App\Http\Middleware\MaintenanceModeMiddleware::class, \App\Http\Middleware\TrackSiteVisit::class]);
        DB::table('basic_payments')->insert([['key' => 'paystack_status', 'value' => 'active'], ['key' => 'paystack_secret_key', 'value' => 'test-secret']]);
        SubscriptionPaymentSetting::create(['is_active' => true, 'bank_name' => 'Equity Bank', 'paybill_number' => '247247', 'account_number' => '0123456789']);
        // Like the live site: services exist, so facility plans with a services limit load the facility-service link.
        \Illuminate\Support\Facades\DB::table('services')->insert(['name' => 'X-ray', 'slug' => 'x-ray', 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }

    /** A freshly registered account: just what the sign-up form collects. */
    private function newClient(string $type, string $suffix)
    {
        $model = ProviderPaystackService::model($type);
        $email = "$type-$suffix@example.test";
        $values = ['name' => ucfirst($type).' '.$suffix, 'slug' => "$type-$suffix", 'phone' => '0700000000', 'password' => bcrypt('Passw0rd!'), 'email_verified_at' => now(),
            in_array($type, ['doctor', 'clinic']) ? 'email' : 'official_email' => $email, 'registration_token' => "tok-$type-$suffix"];
        if ($type === 'doctor') $values += ['department_id' => \Modules\Doctor\app\Models\Department::create(['slug' => "dept-$suffix", 'status' => 1])->id, 'location_id' => \Modules\Doctor\app\Models\Location::create(['status' => 1])->id];
        $client = (new $model)->forceFill($values);
        $client->save();
        return [$client, $email];
    }

    private function pay(string $method, $client, string $type, ?string $plan = null): void
    {
        $plan ??= app(\Modules\Doctor\app\Services\SubscriptionPlanService::class)->for($type)[0]['id'];
        if ($method === 'paystack') {
            $service = app(ProviderPaystackService::class);
            $payment = $service->createAttempt($client, $type, $plan);
            $service->settle(['reference' => $payment->gateway_reference, 'status' => 'success', 'amount' => (int) round($payment->amount * 100), 'currency' => 'KES', 'customer' => ['email' => $client->email ?? $client->official_email]]);
            return;
        }
        $admin = Admin::first() ?? tap(new Admin, fn ($a) => $a->forceFill(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => bcrypt('x'), 'status' => 'active', 'is_super_admin' => 1])->save());
        $manual = app(ManualPaymentService::class);
        if ($method === 'paybill') {
            $manual->approve($manual->submitCode($manual->startPaybill($client, $type, $plan), 'ABC'.strtoupper(substr(md5($client->email ?? $client->official_email), 0, 7))), $admin);
        } else {
            $manual->adminActivate($client, $type, $plan, 0, null, null, $admin);
        }
    }

    private function login(string $type, string $email)
    {
        $url = in_array($type, ['doctor', 'clinic']) ? route($type.'.login') : route('website.partner.login.store', ['type' => $type]);
        return $this->post($url, ['email' => $email, 'password' => 'Passw0rd!']);
    }

    public function test_paid_providers_of_every_type_and_payment_method_can_sign_in_and_open_their_portal(): void
    {
        $pages = [
            'doctor' => ['doctor.dashboard', 'doctor.edit-profile', 'doctor.documents.index', 'listing.receipts:doctor'],
            'clinic' => ['clinic.dashboard', 'clinic.edit-profile', 'clinic.documents.index', 'clinic.subscription.status', 'listing.receipts:clinic'],
            'school' => ['website.partner.dashboard', 'website.partner.profile', 'website.partner.documents.index', 'website.partner.account', 'listing.receipts:school'],
            'supplier' => ['website.partner.dashboard', 'website.partner.profile', 'website.partner.documents.index', 'website.partner.account', 'listing.receipts:supplier'],
        ];
        foreach (['paystack', 'paybill', 'admin'] as $method) {
            foreach ($pages as $type => $routes) {
              // Every plan with Paystack: higher plans unlock profile sections (e.g. facility services) that lower ones never load.
              $plans = $method === 'paystack' ? array_column(app(\Modules\Doctor\app\Services\SubscriptionPlanService::class)->for($type), 'id') : [null];
              foreach ($plans as $plan) {
                [$client, $email] = $this->newClient($type, $method.($plan ? "-$plan" : ''));
                $this->pay($method, $client, $type, $plan);
                $this->assertTrue($client->fresh()->exists);

                $response = $this->login($type, $email);
                $this->assertLessThan(500, $response->status(), "$type/$method login: ".$this->errorOf($response));
                $this->assertTrue(auth($type)->check(), "$type/$method is signed in after login (got ".$response->status().' to '.$response->headers->get('Location').')');
                // Follow the redirects a browser would follow.
                for ($i = 0; $i < 4 && $response->isRedirect(); $i++) {
                    $response = $this->get($response->headers->get('Location'));
                    $this->assertLessThan(500, $response->status(), "$type/$method after login: ".$this->errorOf($response));
                }
                foreach ($routes as $route) {
                    [$name, $param] = array_pad(explode(':', $route), 2, null);
                    $url = $param ? route($name, ['type' => $param]) : (str_starts_with($name, 'website.partner.') ? route($name, ['type' => $type]) : route($name));
                    $page = $this->get($url);
                    $this->assertLessThan(500, $page->status(), "$type/$method $name: ".$this->errorOf($page));
                }
                auth($type)->logout();
                $this->flushSession();
              }
            }
        }
    }

    public function test_accounts_created_by_admin_sign_in_with_the_emailed_password_after_paying(): void
    {
        \Illuminate\Support\Facades\Http::fake(['api.paystack.co/transaction/initialize' => \Illuminate\Support\Facades\Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/x', 'access_code' => 'x']])]);
        $admin = tap(new Admin, fn ($a) => $a->forceFill(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => bcrypt('x'), 'status' => 'active', 'is_super_admin' => 1])->save());
        $department = \Modules\Doctor\app\Models\Department::create(['slug' => 'dept-admin', 'status' => 1])->id;
        $location = \Modules\Doctor\app\Models\Location::create(['status' => 1])->id;
        $home = ['doctor' => 'doctor.dashboard', 'clinic' => 'clinic.dashboard', 'school' => 'website.partner.dashboard', 'supplier' => 'website.partner.dashboard'];
        foreach ($home as $type => $dashboard) {
            $this->actingAs($admin, 'admin');
            $email = "admin-made-$type@example.test";
            $plan = app(\Modules\Doctor\app\Services\SubscriptionPlanService::class)->for($type)[0]['id'];
            $this->post(route('admin.clients.store'), ['type' => $type, 'name' => "Admin made $type", 'email' => $email, 'phone' => '0711000000', 'package' => $plan, 'department_id' => $department, 'location_id' => $location])
                ->assertSessionHas('alert-type', 'success');
            $payment = \App\Models\ProviderPaymentTransaction::where('gateway_payload->expected_email', $email)->sole();
            app(ProviderPaystackService::class)->settle(['reference' => $payment->gateway_reference, 'status' => 'success', 'amount' => (int) round($payment->amount * 100), 'currency' => 'KES', 'customer' => ['email' => $email]]);
            $password = null;
            Mail::assertSent(\App\Mail\ClientAccountActivated::class, function ($mail) use ($email, &$password) {
                if ($mail->loginEmail === $email) $password = $mail->loginPassword;
                return $mail->loginEmail === $email;
            });
            auth('admin')->logout();

            $url = in_array($type, ['doctor', 'clinic']) ? route($type.'.login') : route('website.partner.login.store', ['type' => $type]);
            $response = $this->post($url, ['email' => $email, 'password' => $password]);
            $this->assertLessThan(500, $response->status(), "$type login: ".$this->errorOf($response));
            $this->assertTrue(auth($type)->check(), "$type admin-made account signed in (got ".$response->status().' '.$response->headers->get('Location').' '.json_encode(session('message')).')');
            for ($i = 0; $i < 4 && $response->isRedirect(); $i++) {
                $response = $this->get($response->headers->get('Location'));
                $this->assertLessThan(500, $response->status(), "$type after login: ".$this->errorOf($response));
            }
            auth($type)->logout();
            $this->flushSession();
        }
    }

    public function test_passwords_with_special_characters_work_including_ones_saved_by_the_old_filter(): void
    {
        foreach (['doctor', 'school'] as $type) {
            [$client, $email] = $this->newClient($type, 'symbols');
            $this->pay('admin', $client, $type);
            $url = in_array($type, ['doctor', 'clinic']) ? route($type.'.login') : route('website.partner.login.store', ['type' => $type]);

            // A password with "<" works exactly as typed.
            $client->forceFill(['password' => bcrypt('Ab<cd>12&x')])->save();
            $this->post($url, ['email' => $email, 'password' => 'Ab<cd>12&x']);
            $this->assertTrue(auth($type)->check(), "$type: password with < signs in");
            auth($type)->logout(); $this->flushSession();

            // Saved through the old filter ("Ab12&x"): still signs in with what the person typed, and the real password replaces it.
            $client->forceFill(['password' => bcrypt(\App\Support\PasswordInput::legacyFiltered('Ab<cd>12&x'))])->save();
            $this->post($url, ['email' => $email, 'password' => 'Ab<cd>12&x']);
            $this->assertTrue(auth($type)->check(), "$type: old-filter password signs in");
            $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Ab<cd>12&x', $client->fresh()->password), "$type: real password saved");
            auth($type)->logout(); $this->flushSession();
            $this->post($url, ['email' => $email, 'password' => 'wrong<password']);
            $this->assertFalse(auth($type)->check(), "$type: wrong password still refused");
        }
    }

    private function errorOf($response): string
    {
        return $response->exception ? get_class($response->exception).': '.$response->exception->getMessage().' @ '.$response->exception->getFile().':'.$response->exception->getLine() : (string) $response->status();
    }
}
