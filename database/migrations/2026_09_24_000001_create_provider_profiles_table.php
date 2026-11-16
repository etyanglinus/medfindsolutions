<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('provider_type', 40);
            $table->unsignedBigInteger('provider_id');
            $table->string('status', 30)->default('draft');
            $table->string('verification_status', 30)->default('unverified');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['provider_type', 'provider_id']);
            $table->index(['provider_type', 'status']);
            $table->index(['provider_type', 'verification_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_profiles');
    }
};
