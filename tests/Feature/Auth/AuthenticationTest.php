<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Installer\app\Models\Configuration;
use App\Http\Middleware\DemoModeMiddleware;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.app_mode' => 'LIVE']);
        config(['app.url' => 'http://localhost']);
        \Illuminate\Support\Facades\URL::forceRootUrl('http://localhost');
        $this->withoutMiddleware(DemoModeMiddleware::class);
        Configuration::create(['config' => 'setup_complete', 'value' => 1]);
        Cache::put('setup_complete_status', true);
        Cache::put('setting', new class {
            public string $app_name = 'MedFind';
            public string $recaptcha_status = 'inactive';
            public bool $maintenance_mode = false;
            public string $timezone = 'Africa/Nairobi';

            public function __get(string $name): mixed
            {
                return null;
            }
        });
        DB::table('settings')->insert(['key' => 'app_name', 'value' => 'MedFind', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('patient-login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post(route('patient-login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }
}
