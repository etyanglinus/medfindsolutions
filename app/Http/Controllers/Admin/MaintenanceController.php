<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MaintenanceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Start maintenance for a set time (it ends by itself) or until switched off; stop it now. */
class MaintenanceController extends Controller
{
    public const DURATIONS = [15, 30, 60, 120, 240, 720, 1440];

    public function start(Request $request, MaintenanceService $maintenance): RedirectResponse
    {
        checkAdminHasPermissionAndThrowException('setting.update');
        $data = $request->validate([
            'duration' => ['required', Rule::in(array_merge(array_map('strval', self::DURATIONS), ['until', 'manual']))],
            'until' => ['nullable', 'required_if:duration,until', 'date', 'after:now'],
        ], ['until.after' => __('The end time must be in the future.'), 'until.required_if' => __('Choose when maintenance should end.')]);

        $until = match ($data['duration']) {
            'manual' => null,
            'until' => Carbon::parse($data['until']),
            default => now()->addMinutes((int) $data['duration']),
        };
        $maintenance->start($until);

        $message = $until
            ? __('Maintenance is on until :time. The site comes back automatically.', ['time' => $until->format('j M Y, g:i A')])
            : __('Maintenance is on until you switch it off.');
        return back()->with(['message' => $message, 'alert-type' => 'success']);
    }

    public function stop(MaintenanceService $maintenance): RedirectResponse
    {
        checkAdminHasPermissionAndThrowException('setting.update');
        $maintenance->stop();
        return back()->with(['message' => __('Maintenance is off. The site is open again.'), 'alert-type' => 'success']);
    }
}
