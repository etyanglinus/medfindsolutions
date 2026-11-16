<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The old maintenance switch saved "1" but the old check looked for "active", so switching it on
 * never did anything and a stale "1" could be left in the database. Now that the switch works,
 * that leftover value took the live site down on deploy. Clear it once; maintenance started from
 * the new controls (which always have this migration already applied) is unaffected.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::table('settings')->where('key', 'maintenance_mode')->update(['value' => '0']);
        DB::table('settings')->where('key', 'maintenance_until')->update(['value' => '']);
        Cache::forget('setting');
    }

    public function down(): void
    {
    }
};
