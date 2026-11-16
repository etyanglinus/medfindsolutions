<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        $names = \App\Traits\PermissionsTrait::$clientOnboardingPermissions['permissions'];
        foreach ($names as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin'], ['group_name' => 'client payments']);
            foreach (Role::where('guard_name', 'admin')->get() as $role) {
                if ($role->name === 'Super Admin' || ($name === 'client.payment.view' && $role->hasPermissionTo('setting.view')) || ($name === 'client.payment.confirm' && $role->hasPermissionTo('setting.update'))) {
                    $role->givePermissionTo($permission);
                }
            }
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
    public function down(): void
    {
        Permission::where('guard_name', 'admin')->whereIn('name', \App\Traits\PermissionsTrait::$clientOnboardingPermissions['permissions'])->delete();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
