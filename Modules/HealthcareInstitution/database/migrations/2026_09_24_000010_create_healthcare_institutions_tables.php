<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('healthcare_institutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_profile_id')->nullable()->unique()->constrained('provider_profiles')->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('institution_type', 80)->nullable();
            $table->string('ownership', 30)->nullable();
            $table->string('official_email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('contact_person')->nullable();
            $table->string('website')->nullable();
            $table->string('whatsapp', 40)->nullable();
            $table->string('logo')->nullable();
            $table->string('cover_image')->nullable();
            $table->text('about')->nullable();
            $table->string('location')->nullable();
            $table->text('facilities')->nullable();
            $table->text('practical_training')->nullable();
            $table->text('accommodation')->nullable();
            $table->text('international_pathways')->nullable();
            $table->timestamps();
        });

        Schema::create('healthcare_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('healthcare_institution_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->text('intake_information')->nullable();
            $table->string('fees')->nullable();
            $table->text('application_information')->nullable();
            $table->string('approval_status', 30)->default('pending');
            $table->timestamps();

            $table->unique(['healthcare_institution_id', 'slug']);
            $table->index(['name', 'approval_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('healthcare_courses');
        Schema::dropIfExists('healthcare_institutions');
    }
};
