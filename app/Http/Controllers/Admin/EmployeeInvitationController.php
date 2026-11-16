<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\EmployeeInvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EmployeeInvitationController extends Controller
{
    private function owner(): void { abort_unless(auth('admin')->user()?->is_super_admin, 403); }
    public function index() {
        $this->owner();
        return view('admin.employees.invite', ['employees' => Admin::notSuperAdmin()->latest()->paginate(20)]);
    }
    public function store(Request $request, EmployeeInvitationService $service) {
        $this->owner();
        $request->validate(['emails' => ['nullable','string','max:20000'], 'csv' => ['nullable','file','max:256','mimes:csv,txt']]);
        $rows = [];
        foreach (preg_split('/[\s,;]+/', trim((string) $request->input('emails')), -1, PREG_SPLIT_NO_EMPTY) as $email) $rows[] = ['email' => strtolower($email), 'name' => null];
        if ($request->hasFile('csv')) {
            $handle = fopen($request->file('csv')->getRealPath(), 'r');
            $header = null;
            while (($row = fgetcsv($handle)) !== false) {
                if (!array_filter($row, fn ($value) => trim((string) $value) !== '')) continue;
                $row[0] = ltrim((string) ($row[0] ?? ''), "\xEF\xBB\xBF");
                if ($header === null && in_array('email', array_map(fn ($value) => strtolower(trim((string) $value)), $row), true)) {
                    $header = array_map(fn ($value) => strtolower(trim((string) $value)), $row); continue;
                }
                $emailIndex = $header ? array_search('email', $header, true) : 0;
                $nameIndex = $header ? array_search('name', $header, true) : false;
                $rows[] = ['email' => strtolower(trim((string) ($row[$emailIndex] ?? ''))), 'name' => $nameIndex !== false ? trim((string) ($row[$nameIndex] ?? '')) : null];
                if (count($rows) > 100) break;
            }
            fclose($handle);
        }
        if (!$rows || count($rows) > 100) throw ValidationException::withMessages(['emails' => __('Provide between 1 and 100 employee email addresses.')]);
        Validator::make(['employees' => $rows], ['employees.*.email' => ['required','email','max:255','distinct','unique:admins,email'], 'employees.*.name' => ['nullable','string','max:255']])->validate();
        \Illuminate\Support\Facades\DB::transaction(function () use ($rows, $service) {
            foreach ($rows as $row) $service->invite($row['email'], $row['name']);
        });
        return back()->with('message', count($rows).__(' Sales accounts created. Invitation emails are queued; delivery status appears below.'));
    }
    public function resend(Admin $employee, EmployeeInvitationService $service) {
        $this->owner();
        abort_if($employee->is_super_admin || !$employee->must_change_password, 403);
        $service->invite($employee->email, $employee->name, $employee);
        return back()->with('message', __('A new temporary password invitation has been queued. The previous temporary password is invalid.'));
    }
    public function editPassword() { return view('admin.employees.change-password'); }
    public function updatePassword(Request $request) {
        $data = $request->validate(['current_password' => ['required','string'], 'password' => ['required','string','min:12','max:100','confirmed','different:current_password']]);
        $employee = auth('admin')->user();
        if (!Hash::check($data['current_password'], $employee->password)) throw ValidationException::withMessages(['current_password' => __('The temporary password is incorrect.')]);
        $employee->forceFill(['password' => Hash::make($data['password']), 'must_change_password' => false, 'temporary_password_expires_at' => null, 'remember_token' => \Illuminate\Support\Str::random(60)])->save();
        $request->session()->regenerate();
        return redirect()->route('admin.dashboard')->with('message', __('Password changed. Welcome to the Sales dashboard.'));
    }
}
