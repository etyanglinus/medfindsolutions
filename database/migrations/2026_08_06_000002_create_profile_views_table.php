<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('profile_views', function (Blueprint $table) {
            $table->id();
            $table->string('profile_type', 20);
            $table->unsignedBigInteger('profile_id');
            $table->date('viewed_on');
            $table->string('session_hash', 64);
            $table->timestamps();

            $table->unique(['profile_type', 'profile_id', 'viewed_on', 'session_hash'], 'profile_views_unique_daily_viewer');
            $table->index(['profile_type', 'profile_id', 'viewed_on'], 'profile_views_profile_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_views');
    }
};
