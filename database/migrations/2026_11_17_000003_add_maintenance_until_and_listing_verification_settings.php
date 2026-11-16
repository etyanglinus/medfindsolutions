<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * maintenance_until: when timed maintenance ends (empty = until switched off by hand).
 * require_verified_listing: list specialists and facilities only with an approved, unexpired
 * licence. Starts off so the live directory does not empty itself before licences are approved.
 */
return new class extends Migration {
    public function up(): void
    {
        foreach (['maintenance_until' => '', 'require_verified_listing' => '0'] as $key => $value) {
            if (!DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert(['key' => $key, 'value' => $value, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        // The old toggle stored 1/0 while the check expected "active"; normalise to 1/0.
        DB::table('settings')->where('key', 'maintenance_mode')->where('value', 'active')->update(['value' => '1']);
        Cache::forget('setting');
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['maintenance_until', 'require_verified_listing'])->delete();
        Cache::forget('setting');
    }
};
