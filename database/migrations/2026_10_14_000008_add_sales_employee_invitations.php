<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('admins', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('temporary_password_expires_at')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('invitation_sent_at')->nullable();
            $table->timestamp('invitation_failed_at')->nullable();
        });
        $role = app(\App\Services\SalesRoleService::class)->ensure();
        \App\Models\Admin::notSuperAdmin()->each(function ($employee) use ($role) {
            $employee->syncRoles([$role]);
            $employee->syncPermissions([]);
        });
    }
    public function down(): void {
        Schema::table('admins', fn (Blueprint $table) => $table->dropColumn(['must_change_password','temporary_password_expires_at','invited_at','invitation_sent_at','invitation_failed_at']));
    }
};
