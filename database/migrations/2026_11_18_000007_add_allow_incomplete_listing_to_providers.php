<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['doctors', 'clinics'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->boolean('allow_incomplete_listing')->default(false);
            });
        }
    }

    public function down(): void
    {
        foreach (['doctors', 'clinics'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('allow_incomplete_listing');
            });
        }
    }
};
