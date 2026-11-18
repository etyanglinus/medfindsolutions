<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    public const ADMIN = '/admin/dashboard';

    public const DOCTOR = '/doctor/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Tafuta chat: per visitor, 10 messages a minute and 60 a day (the site-wide daily cap is a setting).
        RateLimiter::for('tafuta', fn (Request $request) => [
            \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by('tafuta:'.$request->ip()),
            \Illuminate\Cache\RateLimiting\Limit::perDay(60)->by('tafuta-day:'.$request->ip())->response(fn () => response()->json(['reply' => __('You have reached today\'s limit for Tafuta. You can keep searching the directory from the menu.'), 'cards' => [], 'emergency' => false], 429)),
        ]);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
