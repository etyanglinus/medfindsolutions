<?php
namespace App\Jobs;
use App\Models\Admin;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class SendEmployeeInvitation implements ShouldQueue, ShouldBeEncrypted
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $tries = 3;
    public function __construct(public int $employeeId, public string $temporaryPassword) {}
    public function backoff(): array { return [60,300,900]; }
    public function handle(): void {
        $employee = Admin::find($this->employeeId);
        if (!$employee || $employee->is_super_admin || !$employee->must_change_password || $employee->status !== 'active'
            || !$employee->temporary_password_expires_at?->isFuture() || !Hash::check($this->temporaryPassword, $employee->password)) return;
        Mail::to($employee->email)->send(new \App\Mail\EmployeeInvitation($employee->name, $this->temporaryPassword, route('admin.login')));
        $employee->forceFill(['invitation_sent_at' => now(), 'invitation_failed_at' => null])->save();
    }
    public function failed(?\Throwable $exception): void {
        $employee = Admin::find($this->employeeId);
        if ($employee && $employee->must_change_password && Hash::check($this->temporaryPassword, $employee->password)) $employee->forceFill(['invitation_failed_at' => now()])->save();
    }
}
