<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Records which monthly performance emails were sent, so each provider gets each month once. */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('provider_monthly_reports', function (Blueprint $table) {
            $table->id();
            $table->string('provider_type', 20);
            $table->unsignedBigInteger('provider_id');
            $table->char('period', 7); // YYYY-MM the report covers
            $table->json('totals')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['provider_type', 'provider_id', 'period'], 'provider_monthly_report_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_monthly_reports');
    }
};
