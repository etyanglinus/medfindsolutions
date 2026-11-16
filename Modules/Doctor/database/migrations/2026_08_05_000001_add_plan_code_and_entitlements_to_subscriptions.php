<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        foreach (['doctor_subscriptions', 'clinic_subscriptions'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('plan_code')->nullable()->after('package_name');
                $blueprint->json('entitlements')->nullable()->after('plan_duration');
                $blueprint->timestamp('expires_at')->nullable()->after('approved_at');
            });
        }
    }

    public function down(): void {
        foreach (['doctor_subscriptions', 'clinic_subscriptions'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn(['plan_code', 'entitlements', 'expires_at']);
            });
        }
    }
};
