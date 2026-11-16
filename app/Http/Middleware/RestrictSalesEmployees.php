<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class RestrictSalesEmployees
{
    public function handle(Request $request, Closure $next)
    {
        $name = $request->route()?->getName() ?? '';
        if (!str_starts_with($name, 'admin.') && !$request->is('admin', 'admin/*')) return $next($request);
        $admin = auth('admin')->user();
        if (!$admin) return $next($request);
        if ($admin->status !== 'active') {
            auth('admin')->logout();
            return redirect()->route('admin.login')->withErrors(['email' => __('Your employee account is inactive.')]);
        }
        if ($admin->must_change_password) {
            if (!$admin->temporary_password_expires_at || $admin->temporary_password_expires_at->isPast()) {
                auth('admin')->logout();
                return redirect()->route('admin.login')->withErrors(['email' => __('Your temporary password expired. Ask the administrator for a new invitation.')]);
            }
            if (!in_array($name, ['admin.employee-password.edit','admin.employee-password.update','admin.logout'], true)) {
                if ($request->expectsJson()) return response()->json(['message' => __('Change your temporary password first.')], 403);
                return redirect()->route('admin.employee-password.edit');
            }
        }
        if ($admin->is_super_admin) return $next($request);
        $allowed = ['admin.home', 'admin.dashboard', 'admin.clients.create', 'admin.clients.store', 'admin.doctor.index', 'admin.clinic-listing.index',
            'admin.provider-profiles.index', 'admin.edit-profile', 'admin.profile-update', 'admin.update-password',
            'admin.employee-password.edit', 'admin.employee-password.update', 'admin.logout', 'admin.login', 'admin.store-login'];
        abort_unless(in_array($name, $allowed, true), 403, __('Sales employees can only access client onboarding and their own account.'));
        return $next($request);
    }
}
