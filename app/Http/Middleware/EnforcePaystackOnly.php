<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnforcePaystackOnly
{
    public function handle(Request $request, Closure $next)
    {
        $route = $request->route();
        if (!$route || str_starts_with($route->getName() ?? '', 'admin.')) return $next($request);
        $action = $route->getAction('controller') ?? $route->getAction('uses');
        if (!is_string($action)) return $next($request);
        if (str_contains($action, 'Payment') && preg_match('/(paypal|stripe|mollie|razorpay|flutterwave|instamojo|bank)/i', $action)) abort(410, __('Only Paystack payments are supported.'));
        if ($route->parameter('method') !== null && (str_contains($route->uri(), 'place-order') || str_contains($route->uri(), 'payment-api'))) {
            abort_unless($route->parameter('method') === 'paystack', 422, __('Only Paystack payments are supported.'));
        }
        return $next($request);
    }
}
