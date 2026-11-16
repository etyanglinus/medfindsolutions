<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('clinics', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('specialty')->nullable();
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('whatsapp')->nullable();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('google_maps_link')->nullable();
            $table->string('insurance_accepted')->nullable();
            $table->string('operating_hours')->nullable();
            $table->boolean('emergency_available')->default(false);
            $table->boolean('reviews_enabled')->default(false);
            $table->tinyInteger('status')->default(0); // 0=inactive, 1=active
            $table->string('password');
            $table->string('registration_token')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('email_verified_token')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('clinics');
    }
};
