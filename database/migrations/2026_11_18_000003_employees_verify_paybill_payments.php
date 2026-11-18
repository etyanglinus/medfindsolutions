<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Employees (Sales role) can see and approve M-Pesa paybill payments from their dashboard.
 * Switching a plan on by hand ("Activate plan", which can be free) gets its own permission,
 * client.plan.activate, kept for Super Admin and roles that can refund payments.
 */
return new class extends Migration {
    public function up(): void
    {
        foreach (['client.payment.view', 'client.payment.approve', 'client.plan.activate'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin'], ['group_name' => 'client payments']);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        // Add (never sync) so any permission an owner gave the Sales role by hand is kept.
        Role::where('name', \App\Services\SalesRoleService::NAME)->where('guard_name', 'admin')->get()
            ->each(fn ($role) => $role->givePermissionTo(['client.payment.view', 'client.payment.approve']));
        foreach (Role::where('guard_name', 'admin')->get() as $role) {
            if ($role->name === 'Super Admin' || $role->hasPermissionTo('client.payment.refund')) $role->givePermissionTo('client.plan.activate');
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('guard_name', 'admin')->where('name', 'client.plan.activate')->delete();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
