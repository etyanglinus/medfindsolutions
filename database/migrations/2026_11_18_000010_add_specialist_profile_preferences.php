<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->boolean('telemedicine_available')->default(false);
            $table->boolean('emergency_available')->default(false);
            $table->boolean('reviews_enabled')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn(['telemedicine_available', 'emergency_available', 'reviews_enabled']);
        });
    }
};
