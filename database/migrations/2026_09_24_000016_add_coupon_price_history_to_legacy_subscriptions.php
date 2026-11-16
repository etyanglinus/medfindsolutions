<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['doctor_subscriptions', 'clinic_subscriptions'] as $tableName) {
            if (! Schema::hasTable($tableName)) continue;
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'coupon_code')) $table->string('coupon_code', 60)->nullable();
                if (! Schema::hasColumn($tableName, 'original_price')) $table->decimal('original_price', 12, 2)->nullable();
                if (! Schema::hasColumn($tableName, 'discount_amount')) $table->decimal('discount_amount', 12, 2)->default(0);
            });
        }
    }

    public function down(): void
    {
        foreach (['doctor_subscriptions', 'clinic_subscriptions'] as $tableName) {
            if (Schema::hasTable($tableName)) Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn(['coupon_code', 'original_price', 'discount_amount']));
        }
    }
};
