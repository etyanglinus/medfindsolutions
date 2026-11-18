<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tafuta, the public chat assistant. A conversation is kept only so a visitor can continue it,
 * and is deleted 7 days after its last message (providers:prune-tafuta, daily).
 * Settings: tafuta_status ('1' shows the assistant on the site) and tafuta_daily_limit
 * (messages answered per day across all visitors, a spending cap).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tafuta_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->json('messages');                // API wire format, replayed exactly as returned
            $table->unsignedSmallInteger('turns')->default(0);
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->timestamps();
            $table->index('updated_at');
        });
        foreach (['tafuta_status' => '0', 'tafuta_daily_limit' => '300'] as $key => $value) {
            if (!DB::table('settings')->where('key', $key)->exists()) DB::table('settings')->insert(['key' => $key, 'value' => $value, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tafuta_conversations');
        DB::table('settings')->whereIn('key', ['tafuta_status', 'tafuta_daily_limit'])->delete();
    }
};
