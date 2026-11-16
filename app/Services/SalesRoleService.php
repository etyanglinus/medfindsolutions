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
        foreach ($permissions as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin'], ['group_name' => str_starts_with($name, 'admin.profile.') ? 'admin profile' : explode('.', $name)[0]]);
        }
        $role = Role::firstOrCreate(['name' => self::NAME, 'guard_name' => 'admin']);
        $role->syncPermissions($permissions);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        return $role;
    }
}
