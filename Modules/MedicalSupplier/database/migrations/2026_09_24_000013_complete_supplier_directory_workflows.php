<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('medical_suppliers', 'registration_number')) $table->string('registration_number', 150)->nullable();
            if (! Schema::hasColumn('medical_suppliers', 'verification_body')) $table->string('verification_body')->nullable();
            if (! Schema::hasColumn('medical_suppliers', 'gallery_images')) $table->json('gallery_images')->nullable();
            if (! Schema::hasColumn('medical_suppliers', 'social_links')) $table->json('social_links')->nullable();
            if (! Schema::hasColumn('medical_suppliers', 'is_featured')) $table->boolean('is_featured')->default(false);
            if (! Schema::hasColumn('medical_suppliers', 'promotional_banner')) $table->string('promotional_banner')->nullable();
            if (! Schema::hasColumn('medical_suppliers', 'promotional_banner_ends_at')) $table->date('promotional_banner_ends_at')->nullable();
            if (! Schema::hasColumn('medical_suppliers', 'promotional_banner_approved')) $table->boolean('promotional_banner_approved')->default(false);
        });

        Schema::table('medical_supplier_products', function (Blueprint $table) {
            if (! Schema::hasColumn('medical_supplier_products', 'approval_status')) $table->string('approval_status', 30)->default('pending')->index();
            if (! Schema::hasColumn('medical_supplier_products', 'admin_note')) $table->string('admin_note', 1000)->nullable();
            if (! Schema::hasColumn('medical_supplier_products', 'is_featured')) $table->boolean('is_featured')->default(false);
        });
        \Illuminate\Support\Facades\DB::table('medical_supplier_products')->where('is_published', true)->update(['approval_status' => 'approved']);

        Schema::table('medical_supplier_quotation_requests', function (Blueprint $table) {
            $table->string('phone', 40)->nullable()->change();
        });

        if (! Schema::hasTable('medical_supplier_quotation_status_history')) {
            Schema::create('medical_supplier_quotation_status_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('quotation_request_id');
                $table->string('status', 30);
                $table->string('actor_type', 30);
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->timestamps();
                $table->index(['quotation_request_id', 'created_at'], 'msq_status_history_idx');
                $table->foreign('quotation_request_id', 'mss_qh_request_fk')->references('id')->on('medical_supplier_quotation_requests')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('medical_supplier_analytics_events')) {
            Schema::create('medical_supplier_analytics_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('medical_supplier_id');
                $table->unsignedBigInteger('medical_supplier_product_id')->nullable();
                $table->string('event_type', 30);
                $table->string('visitor_hash', 64)->nullable();
                $table->timestamps();
                $table->index(['medical_supplier_id', 'event_type', 'created_at'], 'mse_supplier_type_created_idx');
                $table->foreign('medical_supplier_id', 'mse_supplier_fk')->references('id')->on('medical_suppliers')->cascadeOnDelete();
                $table->foreign('medical_supplier_product_id', 'mse_product_fk')->references('id')->on('medical_supplier_products')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_supplier_quotation_status_history');
        \Illuminate\Support\Facades\DB::table('medical_supplier_quotation_requests')->whereNull('phone')->update(['phone' => '']);
        Schema::table('medical_supplier_quotation_requests', function (Blueprint $table) {
            $table->string('phone', 40)->nullable(false)->change();
        });
        Schema::dropIfExists('medical_supplier_analytics_events');
        Schema::table('medical_supplier_products', function (Blueprint $table) {
            $drop = array_values(array_filter(['approval_status', 'admin_note', 'is_featured'], fn ($column) => Schema::hasColumn('medical_supplier_products', $column)));
            if ($drop) $table->dropColumn($drop);
        });
        Schema::table('medical_suppliers', function (Blueprint $table) {
            $drop = array_values(array_filter(['registration_number', 'verification_body', 'gallery_images', 'social_links', 'is_featured', 'promotional_banner', 'promotional_banner_ends_at', 'promotional_banner_approved'], fn ($column) => Schema::hasColumn('medical_suppliers', $column)));
            if ($drop) $table->dropColumn($drop);
        });
    }
};
