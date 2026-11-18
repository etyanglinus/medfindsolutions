<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manual M-Pesa paybill payments (e.g. Equity Bank paybill): an on/off switch and the bank name
 * beside the paybill and account numbers that subscription_payment_settings already holds.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('subscription_payment_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_payment_settings', 'is_active')) $table->boolean('is_active')->default(false)->after('id');
            if (!Schema::hasColumn('subscription_payment_settings', 'bank_name')) $table->string('bank_name')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_payment_settings', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'bank_name']);
        });
    }
};
