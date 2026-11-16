<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Habit\app\Models\Habit;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('habit_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Habit::class)->cascadeOnDelete();
            $table->string('lang_code');
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('habit_translations');
    }
};
