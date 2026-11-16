<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Lets admins tell a school why a course was rejected or needs changes. */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('healthcare_courses', 'admin_note')) {
            Schema::table('healthcare_courses', fn (Blueprint $table) => $table->text('admin_note')->nullable()->after('approval_status'));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('healthcare_courses', 'admin_note')) {
            Schema::table('healthcare_courses', fn (Blueprint $table) => $table->dropColumn('admin_note'));
        }
    }
};
