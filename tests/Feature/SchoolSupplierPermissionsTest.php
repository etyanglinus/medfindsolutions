<?php

namespace Tests\Feature;

use App\Exceptions\AccessPermissionDeniedException;
use App\Http\Controllers\Admin\ClientOnboardingController;
use App\Http\Controllers\Admin\ProviderProfileController;
use App\Models\Admin;
use App\Models\ProviderProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Modules\Doctor\app\Services\SubscriptionPlanService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SchoolSupplierPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_groups_have_the_same_seven_actions_and_old_creation_grants_are_preserved(): void
    {
        foreach (['school', 'supplier'] as $group) {
            foreach (['view', 'create', 'translate', 'store', 'edit', 'update', 'delete'] as $action) {
                $this->assertDatabaseHas('permissions', ['name' => "$group.$action", 'group_name' => $group, 'guard_name' => 'admin']);
            }
        }
        $role = Role::create(['name' => 'Supplier onboarding', 'guard_name' => 'admin']);
        $old = Permission::create(['name' => 'client.supplier.create', 'group_name' => 'client onboarding', 'guard_name' => 'admin']);
        $role->givePermissionTo($old);
        $migration = require database_path('migrations/2026_10_14_000002_align_school_supplier_permissions.php');
        $migration->up();
        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('supplier.create'));
        $this->assertTrue($role->hasPermissionTo('supplier.store'));
        $this->assertDatabaseMissing('permissions', ['name' => 'client.supplier.create']);
    }

    public function test_school_view_does_not_expose_supplier_profiles(): void
    {
        $admin = $this->employee();
        $admin->givePermissionTo('school.view');
        ProviderProfile::create(['provider_type' => 'school', 'provider_id' => 90001, 'status' => 'draft']);
        ProviderProfile::create(['provider_type' => 'supplier', 'provider_id' => 90002, 'status' => 'draft']);
        $view = app(ProviderProfileController::class)->index(Request::create('/admin/provider-profiles'));
        $profiles = $view->getData()['profiles'];
        $this->assertSame(1, $profiles->total());
        $this->assertSame('school', $profiles->first()->provider_type);
    }

    public function test_create_permission_alone_cannot_store_a_supplier(): void
    {
        $admin = $this->employee();
        $admin->givePermissionTo('supplier.create');
        $this->expectException(AccessPermissionDeniedException::class);
        app(ClientOnboardingController::class)->store(Request::create('/admin/clients', 'POST', ['type' => 'supplier']), app(SubscriptionPlanService::class));
    }

    public function test_school_only_employee_cannot_open_the_supplier_listing_by_changing_the_type(): void
    {
        $this->employee()->givePermissionTo('school.view');
        $this->expectException(AccessPermissionDeniedException::class);
        app(ProviderProfileController::class)->index(Request::create('/admin/provider-profiles', 'GET', ['type' => 'supplier']));
    }

    private function employee(): Admin
    {
        $admin = new Admin;
        $admin->forceFill(['name' => 'Employee', 'email' => 'employee@example.test', 'password' => bcrypt('password'), 'status' => 'active', 'is_super_admin' => 0])->save();
        $this->actingAs($admin, 'admin');
        return $admin;
    }
}
