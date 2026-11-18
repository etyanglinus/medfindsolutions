<?php
namespace App\Services;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SalesRoleService
{
    public const NAME = 'Sales';
    public function ensure(): Role
    {
        $permissions = ['dashboard.view', 'admin.profile.view', 'admin.profile.update'];
        foreach (['doctor','facility','school','supplier'] as $group) foreach (['view','create','store'] as $action) $permissions[] = "$group.$action";
        // Employees confirm M-Pesa paybill payments from their dashboard (activating plans by hand stays with owners).
        $permissions[] = 'client.payment.view';
        $permissions[] = 'client.payment.approve';
        foreach ($permissions as $name) {
            $group = str_starts_with($name, 'admin.profile.') ? 'admin profile' : (str_starts_with($name, 'client.payment.') ? 'client payments' : explode('.', $name)[0]);
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin'], ['group_name' => $group]);
        }
        $role = Role::firstOrCreate(['name' => self::NAME, 'guard_name' => 'admin']);
        $role->syncPermissions($permissions);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        return $role;
    }
}
