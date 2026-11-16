<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/stripe-webview',
        // One-click unsubscribe from mail apps (no CSRF token possible); the URL itself is signed.
        'provider/email-preferences/*',
    ];
}
