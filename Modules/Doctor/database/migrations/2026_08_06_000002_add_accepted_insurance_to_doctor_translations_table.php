<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('doctor_translations', function (Blueprint $table) {
            $table->text('accepted_insurance')->nullable()->after('licensing_body');
        });
    }

    public function down(): void {
        Schema::table('doctor_translations', fn (Blueprint $table) => $table->dropColumn('accepted_insurance'));
    }
};
