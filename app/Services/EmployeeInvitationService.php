<?php
namespace App\Services;
use App\Models\Admin;
use App\Jobs\SendEmployeeInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeInvitationService
{
    public function invite(string $email, ?string $name = null, ?Admin $existing = null): Admin {
        return DB::transaction(function () use ($email, $name, $existing) {
            $role = app(SalesRoleService::class)->ensure();
            $password = Str::password(24, symbols: false);
            $employee = $existing ?? new Admin;
            abort_if($employee->is_super_admin, 403);
            $employee->forceFill(['name' => $name ?: Str::headline(Str::before($email, '@')), 'email' => $email,
                'password' => Hash::make($password), 'status' => 'active', 'is_super_admin' => false,
                'must_change_password' => true, 'temporary_password_expires_at' => now()->addDays(7),
                'invited_at' => now(), 'invitation_sent_at' => null, 'invitation_failed_at' => null,
                'remember_token' => Str::random(60)])->save();
            $employee->syncRoles([$role]); $employee->syncPermissions([]);
            DB::afterCommit(function () use ($employee, $password) {
                try { SendEmployeeInvitation::dispatch($employee->id, $password); }
                catch (\Throwable $exception) {
                    $employee->forceFill(['invitation_failed_at' => now()])->save();
                    report($exception);
                }
            });
            return $employee;
        });
    }
}
