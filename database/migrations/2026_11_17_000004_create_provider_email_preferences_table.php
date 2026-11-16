<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Optional emails a provider has turned off (e.g. the monthly performance report). No row = everything on. */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('provider_email_preferences', function (Blueprint $table) {
            $table->id();
            $table->string('provider_type', 20);
            $table->unsignedBigInteger('provider_id');
            $table->boolean('monthly_report')->default(true);
            $table->timestamp('monthly_report_changed_at')->nullable();
            $table->timestamps();
            $table->unique(['provider_type', 'provider_id'], 'provider_email_preferences_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_email_preferences');
    }
};
