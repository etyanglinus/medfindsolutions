<?php

namespace App\Http\Middleware;

use App\Models\SiteVisit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackSiteVisit {
    public function handle(Request $request, Closure $next): Response {
        $response = $next($request);

        if (! $request->isMethod('get') || $request->ajax() || $request->is('admin/*') || $request->is('api/*') || $response->getStatusCode() >= 400) {
            return $response;
        }

        $visitorType = match (true) {
            auth('doctor')->check() => 'doctor',
            auth('clinic')->check() => 'facility',
            auth('web')->check() => 'patient',
            default => null,
        };

        if (! $visitorType) {
            return $response;
        }

        SiteVisit::firstOrCreate([
            'visited_on' => today()->toDateString(),
            'session_hash' => hash('sha256', $request->session()->getId()),
            'visitor_type' => $visitorType,
        ]);

        return $response;
    }
}
