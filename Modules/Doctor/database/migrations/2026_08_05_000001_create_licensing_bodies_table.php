<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('licensing_bodies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('doctor');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('licensing_bodies');
    }
};
