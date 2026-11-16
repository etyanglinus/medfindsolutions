<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        $old = Permission::where('guard_name', 'admin')->where('name', 'client.payment.confirm')->first();
        $target = Permission::where('guard_name', 'admin')->where('name', 'client.payment.refund')->first();
        if ($old && !$target) {
            $old->update(['name' => 'client.payment.refund']);
            $target = $old;
        }
        if (!$target) $target = Permission::create(['name' => 'client.payment.refund', 'guard_name' => 'admin', 'group_name' => 'client payments']);
        if ($old && $old->id !== $target->id) {
            foreach (['role_has_permissions', 'model_has_permissions'] as $table) {
                foreach (\Illuminate\Support\Facades\DB::table($table)->where('permission_id', $old->id)->get() as $row) {
                    $data = (array) $row; $data['permission_id'] = $target->id;
                    \Illuminate\Support\Facades\DB::table($table)->insertOrIgnore($data);
                }
            }
            $old->delete();
        }
        Role::where('name', 'Super Admin')->where('guard_name', 'admin')->get()->each(fn ($role) => $role->givePermissionTo($target));
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
    public function down(): void { /* Preserve existing role assignments. */ }
};
