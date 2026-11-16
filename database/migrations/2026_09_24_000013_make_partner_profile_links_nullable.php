<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['healthcare_institutions', 'medical_suppliers'] as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'provider_profile_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('provider_profile_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Keep the nullable link to permit atomic account and profile creation.
    }
};
