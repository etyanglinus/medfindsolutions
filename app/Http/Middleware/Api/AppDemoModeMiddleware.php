<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppDemoModeMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response {

        if (strtoupper(env('APP_MODE')) !== 'LIVE') {
            $allowedRoutes = [
                'api.patient-register', 'api.patient-login', 'api.patient-forget-password', 'api.patient-reset-password', 'api.patient-logout', 'api.patient-logoutAllApp', 'api.doctor-register', 'api.doctor-login', 'api.doctor-forget-password', 'api.doctor-reset-password', 'api.doctor-logout', 'api.doctor-logoutAllApp',
            ];

            if (request()->routeIs(...$allowedRoutes) || request()->method() == 'GET') {
                return $next($request);
            } else {
                return response()->json(['status' => 'failed', 'message' => __('In Demo Mode You Can Not Perform This Action')], 403);
            }
        }

        return $next($request);
    }
}
