<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['clinics', 'healthcare_institutions', 'medical_suppliers', 'provider_profiles'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->softDeletes());
        }
    }
    public function down(): void
    {
        foreach (['clinics', 'healthcare_institutions', 'medical_suppliers', 'provider_profiles'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropSoftDeletes());
        }
    }
};
