<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('healthcare_institutions', function (Blueprint $table) {
            $table->string('password')->nullable()->after('provider_profile_id');
            $table->string('registration_token')->nullable()->unique()->after('password');
            $table->timestamp('email_verified_at')->nullable()->after('registration_token');
            $table->string('email_verified_token')->nullable()->after('email_verified_at');
            $table->rememberToken();
        });

        Schema::table('medical_suppliers', function (Blueprint $table) {
            $table->string('password')->nullable()->after('provider_profile_id');
            $table->string('registration_token')->nullable()->unique()->after('password');
            $table->timestamp('email_verified_at')->nullable()->after('registration_token');
            $table->string('email_verified_token')->nullable()->after('email_verified_at');
            $table->rememberToken();
        });
    }

    public function down(): void
    {
        Schema::table('medical_suppliers', function (Blueprint $table) {
            $table->dropRememberToken();
            $table->dropColumn(['password', 'registration_token', 'email_verified_at', 'email_verified_token']);
        });

        Schema::table('healthcare_institutions', function (Blueprint $table) {
            $table->dropRememberToken();
            $table->dropColumn(['password', 'registration_token', 'email_verified_at', 'email_verified_token']);
        });
    }
};
