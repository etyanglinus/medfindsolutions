<?php

namespace App\Http\Middleware;

use App\Exceptions\DemoModeEnabledException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoModeMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response {
        if (strtoupper(config('app.app_mode')) !== 'LIVE') {
            // Define an array of routes that are allowed in non-LIVE mode
            $allowedRoutes = [
                'patient-register', 'patient-login', 'logout', 'user-verification', 'doctor.register', 'doctor.login', 'doctor.logout', 'admin.store-login', 'admin.logout',
            ];
            $allNotAllowedGetRoutes = [
                'admin.addons.update.status',
            ];
            if (request()->routeIs(...$allowedRoutes) || request()->method() == 'GET' && !request()->routeIs(...$allNotAllowedGetRoutes)) {
                return $next($request);
            } else {
                throw new DemoModeEnabledException();
            }
        }

        return $next($request);
    }
}
