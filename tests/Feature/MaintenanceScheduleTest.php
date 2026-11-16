<?php

namespace Tests\Feature;

use App\Http\Middleware\MaintenanceModeMiddleware;
use App\Models\Admin;
use App\Services\MaintenanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MaintenanceScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['maintenance_mode' => '0', 'maintenance_until' => '', 'maintenance_title' => 'Back soon', 'maintenance_description' => '<p>Upgrading</p>', 'app_name' => 'MedFind'] as $k => $v) {
            DB::table('settings')->updateOrInsert(['key' => $k], ['value' => $v]);
        }
        Cache::forever('setting', (object) DB::table('settings')->pluck('value', 'key')->all());
    }

    private function visit(): \Symfony\Component\HttpFoundation\Response
    {
        return app(MaintenanceModeMiddleware::class)->handle(Request::create('/facilities'), fn () => response('site'));
    }

    public function test_timed_maintenance_blocks_visitors_then_ends_by_itself(): void
    {
        app(MaintenanceService::class)->start(now()->addMinutes(30));

        $response = $this->visit();
        $this->assertSame(503, $response->getStatusCode());
        $this->assertGreaterThan(1700, (int) $response->headers->get('Retry-After'));
        $this->assertStringContainsString('We expect to be back at', $response->getContent());

        $this->travel(31)->minutes();
        $this->assertSame('site', $this->visit()->getContent());
        $this->assertSame('0', DB::table('settings')->where('key', 'maintenance_mode')->value('value'), 'Maintenance switches itself off.');
        $this->assertSame('', DB::table('settings')->where('key', 'maintenance_until')->value('value'));
    }

    public function test_manual_maintenance_lasts_until_switched_off_and_admins_can_browse(): void
    {
        app(MaintenanceService::class)->start(null);
        $this->travel(3)->days();
        $this->assertSame(503, $this->visit()->getStatusCode());

        $admin = new Admin;
        $admin->forceFill(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => bcrypt('x'), 'status' => 'active', 'is_super_admin' => 1])->save();
        $this->actingAs($admin, 'admin');
        $this->assertSame('site', $this->visit()->getContent());

        app(MaintenanceService::class)->stop();
        $this->assertFalse(app(MaintenanceService::class)->isActive());
    }

    public function test_admin_can_start_for_a_duration_and_until_must_be_in_the_future(): void
    {
        $admin = new Admin;
        $admin->forceFill(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => bcrypt('x'), 'status' => 'active', 'is_super_admin' => 1])->save();
        $this->actingAs($admin, 'admin');
        $controller = app(\App\Http\Controllers\Admin\MaintenanceController::class);
        $request = fn (array $data) => tap(Request::create('/admin/maintenance/start', 'POST', $data), fn ($r) => $r->setLaravelSession(app('session.store')));

        $controller->start($request(['duration' => '120']), app(MaintenanceService::class));
        $this->assertTrue(app(MaintenanceService::class)->until()->between(now()->addMinutes(119), now()->addMinutes(121)));

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->start($request(['duration' => 'until', 'until' => now()->subHour()->toDateTimeString()]), app(MaintenanceService::class));
    }
}
