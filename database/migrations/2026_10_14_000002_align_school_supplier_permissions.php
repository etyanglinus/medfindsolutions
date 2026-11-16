<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        foreach (['school', 'supplier'] as $group) {
            foreach (['view', 'create', 'translate', 'store', 'edit', 'update', 'delete'] as $action) {
                $permission = Permission::firstOrCreate(['name' => "$group.$action", 'guard_name' => 'admin'], ['group_name' => $group]);
                Role::where('name', 'Super Admin')->where('guard_name', 'admin')->get()->each(fn ($role) => $role->givePermissionTo($permission));
            }
        }
        foreach (['doctor' => 'doctor', 'clinic' => 'facility', 'school' => 'school', 'supplier' => 'supplier'] as $oldType => $group) {
            $old = Permission::where('name', "client.$oldType.create")->where('guard_name', 'admin')->first();
            if (!$old) continue;
            foreach (['create', 'store'] as $action) {
                $target = Permission::firstOrCreate(['name' => "$group.$action", 'guard_name' => 'admin'], ['group_name' => $group]);
                foreach (['role_has_permissions', 'model_has_permissions'] as $table) {
                    foreach (DB::table($table)->where('permission_id', $old->id)->get() as $row) {
                        $attributes = (array) $row;
                        $attributes['permission_id'] = $target->id;
                        DB::table($table)->insertOrIgnore($attributes);
                    }
                }
            }
            $old->delete();
        }
        Permission::where('guard_name', 'admin')->whereIn('name', ['client.payment.view', 'client.payment.confirm'])->update(['group_name' => 'client payments']);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Preserve reassigned employee grants when rolling back this data migration.
    }
};
