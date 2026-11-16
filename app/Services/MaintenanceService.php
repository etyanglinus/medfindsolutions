<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Site maintenance with an optional end time. While active, visitors and providers see the
 * maintenance page (HTTP 503 with Retry-After, so search engines treat it as temporary); admins
 * signed in to /admin can still browse the site. When the end time passes, maintenance switches
 * itself off on the next request or within a minute via the scheduler.
 */
class MaintenanceService
{
    public function isActive(): bool
    {
        $setting = Cache::get('setting');
        if ((string) ($setting->maintenance_mode ?? '0') !== '1') return false;
        $until = $this->until();
        if ($until && $until->isPast()) {
            $this->stop();
            return false;
        }
        return true;
    }

    public function until(): ?Carbon
    {
        $value = trim((string) (Cache::get('setting')->maintenance_until ?? ''));
        return $value === '' ? null : Carbon::parse($value);
    }

    /** @param ?Carbon $until null = until switched off by hand */
    public function start(?Carbon $until): void
    {
        $this->set(['maintenance_mode' => '1', 'maintenance_until' => $until?->toDateTimeString() ?? '']);
    }

    public function stop(): void
    {
        $this->set(['maintenance_mode' => '0', 'maintenance_until' => '']);
    }

    private function set(array $values): void
    {
        foreach ($values as $key => $value) {
            DB::table('settings')->updateOrInsert(['key' => $key], ['value' => $value, 'updated_at' => now()]);
        }
        Cache::forget('setting');
        // Reload now so the rest of this request sees the new state.
        Cache::rememberForever('setting', fn () => (object) DB::table('settings')->pluck('value', 'key')->all());
    }
}
