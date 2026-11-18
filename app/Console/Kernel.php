<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Cache;

class Kernel extends ConsoleKernel {
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\InActiveSubscriber',
        'App\Console\Commands\PreNotification',
        'App\Console\Commands\SendSubscriptionExpiryReminders',
        'App\Console\Commands\ExpireProviderSubscriptions',
        'App\Console\Commands\SyncProviderRegistry',
    ];
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void {
        $schedule->command('clients:retry-activation')->everyFiveMinutes()->withoutOverlapping();
        // Picks up M-Pesa / bank-transfer payments that finished after the payer left Paystack.
        $schedule->command('paystack:reconcile')->everyTenMinutes()->withoutOverlapping();
        // php artisan schedule:run >> /dev/null 2>&1
        // run queue jobs
        $schedule->command('queue:work --tries=3 --stop-when-empty')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->call(function () {
            Cache::forget('corn_working');
            Cache::remember('corn_working', now()->addHours(24), function () {
                return 'working';
            });
        })->everyFiveMinutes()->name('cron_test')->withoutOverlapping();

        $schedule->command('inactive:subscriber')->hourly();
        $schedule->command('subscriptions:send-expiry-reminders')->dailyAt('08:00')->withoutOverlapping();
        // Monthly listing performance emails: hourly on the 1st-3rd, 100 per run (host email caps).
        $schedule->command('providers:send-monthly-report --limit=100')->hourlyAt(15)->when(fn () => now()->day <= 3)->withoutOverlapping();
        $schedule->command('providers:expire-subscriptions')->hourly()->withoutOverlapping();
        $schedule->command('providers:check-licences')->dailyAt('07:10')->withoutOverlapping();
        $schedule->command('tafuta:prune')->dailyAt('03:20')->withoutOverlapping();
        // Ends timed maintenance on schedule even when nobody visits the site at that moment.
        $schedule->call(fn () => app(\App\Services\MaintenanceService::class)->isActive())->everyMinute()->name('maintenance-auto-end');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
