<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('provider_documents', function (Blueprint $table) {
            $table->id();
            $table->string('provider_type');
            $table->unsignedBigInteger('provider_id');
            $table->string('licensing_body');
            $table->string('verification_body')->nullable();
            $table->string('registration_number');
            $table->date('accreditation_expires_at')->nullable();
            $table->string('file_path');
            $table->string('status')->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamps();
            $table->index(['provider_type', 'provider_id']);
        });

        Schema::create('provider_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('provider_type');
            $table->unsignedBigInteger('provider_id');
            $table->string('patient_name');
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->text('reason');
            $table->string('preferred_contact_method', 30);
            $table->timestamps();
            $table->index(['provider_type', 'provider_id']);
        });

        Schema::create('provider_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('provider_type');
            $table->unsignedBigInteger('provider_id');
            $table->string('reviewer_name');
            $table->string('reviewer_email')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->text('review');
            $table->string('status')->default('published');
            $table->text('report_reason')->nullable();
            $table->timestamps();
            $table->index(['provider_type', 'provider_id']);
        });

        Schema::create('clinic_gallery_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->string('caption')->nullable();
            $table->timestamps();
        });

        Schema::create('clinic_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('address');
            $table->string('phone', 30)->nullable();
            $table->timestamps();
        });

        Schema::table('clinics', function (Blueprint $table) {
            $table->text('equipment')->nullable()->after('description');
            $table->text('featured_services')->nullable()->after('equipment');
        });
    }

    public function down(): void {
        Schema::table('clinics', function (Blueprint $table) {
            $table->dropColumn(['equipment', 'featured_services']);
        });
        Schema::dropIfExists('clinic_branches');
        Schema::dropIfExists('clinic_gallery_images');
        Schema::dropIfExists('provider_reviews');
        Schema::dropIfExists('provider_inquiries');
        Schema::dropIfExists('provider_documents');
    }
};
