<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('medical_suppliers', 'contact_person')) $table->string('contact_person')->nullable()->after('phone');
        });
        Schema::table('medical_supplier_products', function (Blueprint $table) {
            if (! Schema::hasColumn('medical_supplier_products', 'catalogue_is_public')) $table->boolean('catalogue_is_public')->default(false)->after('catalogue_path');
        });
    }

    public function down(): void
    {
        Schema::table('medical_supplier_products', function (Blueprint $table) {
            if (Schema::hasColumn('medical_supplier_products', 'catalogue_is_public')) $table->dropColumn('catalogue_is_public');
        });
        Schema::table('medical_suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('medical_suppliers', 'contact_person')) $table->dropColumn('contact_person');
        });
    }
};
