<?php

namespace App\Http\Middleware;

use App\Services\MaintenanceService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shows the maintenance page while maintenance is on. The response is HTTP 503 with Retry-After
 * (search engines keep the page indexed), the page states when the site is back and reloads
 * itself, and admins signed in to the admin panel can still use the site to check their work.
 */
class MaintenanceModeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $maintenance = app(MaintenanceService::class);
        if (!$maintenance->isActive() || Auth::guard('admin')->check()) {
            return $next($request);
        }

        $until = $maintenance->until();
        $retryAfter = $until ? max(60, now()->diffInSeconds($until, false)) : 3600;
        if ($request->expectsJson()) {
            return response()->json(['message' => __('The site is under maintenance.'), 'back_at' => $until?->toIso8601String()], 503, ['Retry-After' => $retryAfter]);
        }
        return response()->view('maintenance', ['until' => $until], 503, ['Retry-After' => $retryAfter]);
    }
}
