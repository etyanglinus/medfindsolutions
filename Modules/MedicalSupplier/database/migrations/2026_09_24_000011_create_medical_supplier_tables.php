<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_profile_id')->nullable()->unique()->constrained('provider_profiles')->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('official_email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('whatsapp', 40)->nullable();
            $table->string('website')->nullable();
            $table->string('logo')->nullable();
            $table->string('cover_image')->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('year_established')->nullable();
            $table->string('address')->nullable();
            $table->json('counties_served')->nullable();
            $table->json('supplier_types')->nullable();
            $table->json('brands')->nullable();
            $table->json('services')->nullable();
            $table->timestamps();
        });

        Schema::create('medical_supplier_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('medical_supplier_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('medical_supplier_category_supplier', function (Blueprint $table) {
            $table->unsignedBigInteger('medical_supplier_id');
            $table->unsignedBigInteger('medical_supplier_category_id');
            $table->foreign('medical_supplier_id', 'mss_category_supplier_supplier_fk')->references('id')->on('medical_suppliers')->cascadeOnDelete();
            $table->foreign('medical_supplier_category_id', 'mss_category_supplier_category_fk')->references('id')->on('medical_supplier_categories')->cascadeOnDelete();
            $table->primary(['medical_supplier_id', 'medical_supplier_category_id']);
        });

        Schema::create('medical_supplier_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_supplier_id')->constrained('medical_suppliers')->cascadeOnDelete();
            $table->unsignedBigInteger('medical_supplier_category_id')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->text('description')->nullable();
            $table->text('specifications')->nullable();
            $table->string('image')->nullable();
            $table->json('additional_images')->nullable();
            $table->string('availability')->nullable();
            $table->string('catalogue_path')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->unique(['medical_supplier_id', 'slug']);
            $table->index(['name', 'is_published']);
            $table->foreign('medical_supplier_category_id', 'msp_product_category_fk')->references('id')->on('medical_supplier_categories')->nullOnDelete();
        });

        Schema::create('medical_supplier_quotation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_supplier_id')->constrained('medical_suppliers')->cascadeOnDelete();
            $table->unsignedBigInteger('medical_supplier_product_id')->nullable();
            $table->string('name');
            $table->string('organisation')->nullable();
            $table->string('phone', 40);
            $table->string('email')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->text('message');
            $table->string('status', 30)->default('new');
            $table->timestamps();

            $table->index(['medical_supplier_id', 'status'], 'msq_supplier_status_idx');
            $table->foreign('medical_supplier_product_id', 'msq_product_fk')->references('id')->on('medical_supplier_products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_supplier_quotation_requests');
        Schema::dropIfExists('medical_supplier_products');
        Schema::dropIfExists('medical_supplier_category_supplier');
        Schema::dropIfExists('medical_supplier_categories');
        Schema::dropIfExists('medical_suppliers');
    }
};
