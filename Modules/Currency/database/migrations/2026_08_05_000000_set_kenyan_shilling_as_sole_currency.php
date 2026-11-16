<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Make KES the only active and selectable currency in existing installations. */
    public function up(): void
    {
        DB::table('multi_currencies')->where('currency_code', '!=', 'KES')->update([
            'is_default' => 'no',
            'status' => 'inactive',
        ]);

        $kes = [
            'currency_name' => 'KSh-Kenyan Shilling',
            'country_code' => 'KE',
            'currency_icon' => 'KSh',
            'is_default' => 'yes',
            'currency_rate' => 1,
            'currency_position' => 'before_price_with_space',
            'status' => 'active',
            'updated_at' => now(),
        ];

        if (DB::table('multi_currencies')->where('currency_code', 'KES')->exists()) {
            DB::table('multi_currencies')->where('currency_code', 'KES')->update($kes);
        } else {
            DB::table('multi_currencies')->insert($kes + [
                'currency_code' => 'KES',
                'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Historical currency records remain intact; restoring multi-currency is a manual configuration choice.
    }
};
