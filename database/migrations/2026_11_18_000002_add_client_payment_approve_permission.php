<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * "Approve manual payments": confirm M-Pesa paybill codes and switch plans on from the admin panel.
 * Given to Super Admin and to every role that can already refund client payments; other staff can
 * be given it under Manage Admin > Roles.
 */
return new class extends Migration {
    public function up(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'client.payment.approve', 'guard_name' => 'admin'], ['group_name' => 'client payments']);
        foreach (Role::where('guard_name', 'admin')->get() as $role) {
            if ($role->name === 'Super Admin' || $role->hasPermissionTo('client.payment.refund')) $role->givePermissionTo($permission);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('guard_name', 'admin')->where('name', 'client.payment.approve')->delete();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
