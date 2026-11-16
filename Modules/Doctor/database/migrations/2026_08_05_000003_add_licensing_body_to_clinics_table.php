<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('clinics', function (Blueprint $table) {
            if (!Schema::hasColumn('clinics', 'licensing_body')) {
                $table->string('licensing_body')->nullable()->after('location');
            }
        });
    }

    public function down(): void {
        Schema::table('clinics', function (Blueprint $table) {
            if (Schema::hasColumn('clinics', 'licensing_body')) {
                $table->dropColumn('licensing_body');
            }
        });
    }
};
