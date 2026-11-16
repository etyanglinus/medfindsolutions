<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time command to ensure all expected key-value rows exist in
 * basic_payments so the admin payment settings page never throws
 * "Undefined property: stdClass::$xxx".
 *
 * Run: php artisan payments:seed-defaults
 */
class SeedMissingPaymentRows extends Command
{
    protected $signature   = 'payments:seed-defaults';
    protected $description = 'Insert any missing default rows in the basic_payments key-value table';

    public function handle(): int
    {
        $defaults = [
            'stripe_key'              => '',
            'stripe_secret'           => '',
            'stripe_charge'           => '0.00',
            'stripe_status'           => 'inactive',
            'stripe_image'            => '',
            'paypal_app_id'           => '',
            'paypal_client_id'        => '',
            'paypal_secret_key'       => '',
            'paypal_account_mode'     => 'sandbox',
            'paypal_charge'           => '0.00',
            'paypal_status'           => 'inactive',
            'paypal_image'            => '',
            'bank_information'        => '',
            'bank_charge'             => '0.00',
            'bank_status'             => 'inactive',
            'bank_image'              => '',
            'razorpay_key'            => '',
            'razorpay_secret'         => '',
            'razorpay_name'           => '',
            'razorpay_description'    => '',
            'razorpay_charge'         => '0.00',
            'razorpay_theme_color'    => '#6d0ce4',
            'razorpay_status'         => 'inactive',
            'razorpay_image'          => '',
            'flutterwave_public_key'  => '',
            'flutterwave_secret_key'  => '',
            'flutterwave_app_name'    => '',
            'flutterwave_charge'      => '0.00',
            'flutterwave_status'      => 'inactive',
            'flutterwave_image'       => '',
            'paystack_public_key'     => '',
            'paystack_secret_key'     => '',
            'paystack_charge'         => '0.00',
            'paystack_status'         => 'inactive',
            'paystack_image'          => '',
            'mollie_key'              => '',
            'mollie_charge'           => '0.00',
            'mollie_status'           => 'inactive',
            'mollie_image'            => '',
            'instamojo_api_key'       => '',
            'instamojo_auth_token'    => '',
            'instamojo_account_mode'  => 'Sandbox',
            'instamojo_charge'        => '0.00',
            'instamojo_status'        => 'inactive',
            'instamojo_image'         => '',
        ];

        $created = 0;
        $now = now();

        // Fetch existing keys in one query
        $existing = DB::table('basic_payments')->pluck('value', 'key')->all();

        foreach ($defaults as $key => $default) {
            if (! array_key_exists($key, $existing)) {
                DB::table('basic_payments')->insert([
                    'key'        => $key,
                    'value'      => $default,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $this->line("  Created: {$key} = {$default}");
                $created++;
            }
        }

        $this->info("Done. {$created} missing row(s) inserted.");
        return self::SUCCESS;
    }
}
