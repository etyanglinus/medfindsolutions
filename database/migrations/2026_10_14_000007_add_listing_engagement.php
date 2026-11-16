<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('listing_events', function (Blueprint $table) {
            $table->id(); $table->string('provider_type', 20); $table->unsignedBigInteger('provider_id');
            $table->string('event', 30); $table->date('day'); $table->string('visitor', 64); $table->timestamps();
            $table->unique(['provider_type', 'provider_id', 'event', 'day', 'visitor'], 'listing_event_unique');
            $table->index(['provider_type', 'provider_id', 'day']);
        });
        Schema::table('provider_inquiries', fn (Blueprint $table) => $table->string('status', 20)->default('new'));
    }
    public function down(): void {
        Schema::dropIfExists('listing_events');
        Schema::table('provider_inquiries', fn (Blueprint $table) => $table->dropColumn('status'));
    }
};
