<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('site_visits', function (Blueprint $table) {
            $table->id();
            $table->date('visited_on');
            $table->string('session_hash', 64);
            $table->string('visitor_type', 20);
            $table->timestamps();

            $table->unique(['visited_on', 'session_hash', 'visitor_type']);
            $table->index(['visited_on', 'visitor_type']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('site_visits');
    }
};
