<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Facility <-> service link used by the facility profile page on plans with a services limit.
 * The table was once created by hand on a development copy and never had a migration, so on the
 * live database facilities on those plans got a server error after signing in. Created only
 * when missing, so a database that already has it is left untouched.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('clinic_service')) return;
        Schema::create('clinic_service', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('service_id');
            $table->timestamps();
            $table->unique(['clinic_id', 'service_id']);
            $table->index('service_id');
        });
    }

    public function down(): void
    {
        // Never dropped automatically: it holds facility data.
    }
};
