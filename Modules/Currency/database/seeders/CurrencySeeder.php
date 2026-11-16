<?php

namespace Modules\Currency\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Currency\app\Models\MultiCurrency;

class CurrencySeeder extends Seeder
{
    /** Run the database seeds. */
    public function run(): void
    {
        if (! MultiCurrency::first()) {
            // Kenyan shilling is the application's sole base and display currency.
            $currency = new MultiCurrency();
            $currency->currency_name = 'KSh-Kenyan Shilling';
            $currency->country_code = 'KE';
            $currency->currency_code = 'KES';
            $currency->currency_icon = 'KSh';
            $currency->is_default = 'yes';
            $currency->currency_rate = 1;
            $currency->currency_position = 'before_price_with_space';
            $currency->status = 'active';
            $currency->save();
        }
    }
}
