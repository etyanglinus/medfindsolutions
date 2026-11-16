<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;
use App\Models\ProviderPaymentTransaction;

return new class extends Migration {
    public function up(): void
    {
        ProviderPaymentTransaction::where('gateway', 'bank_transfer')->whereIn('status', ['pending', 'initiated'])->chunkById(100, function ($payments) {
            foreach ($payments as $payment) {
                $subscription = $payment->subscription;
                if ($subscription && $subscription->status === 'pending' && data_get($subscription->metadata, 'admin_onboarding')) {
                    $reference = 'MFS-'.Str::uuid();
                    $payment->update(['gateway' => 'paystack', 'gateway_reference' => $reference, 'idempotency_key' => 'paystack:'.$reference, 'status' => 'initiated', 'gateway_payload' => array_merge($payment->gateway_payload ?? [], ['retired_manual_reference' => $payment->gateway_reference])]);
                } else {
                    $payment->update(['status' => 'failed', 'failure_reason' => 'Manual payment gateway retired. Use Paystack.']);
                    if ($subscription && $subscription->status === 'pending') $subscription->update(['status' => 'cancelled', 'cancelled_at' => now()]);
                }
            }
        });
        foreach ([\Modules\Doctor\app\Models\DoctorSubscription::class, \Modules\Doctor\app\Models\ClinicSubscription::class] as $model) $model::where('payment_method', 'bank_transfer')->where('status', 'pending')->update(['status' => 'rejected', 'admin_note' => 'Manual payment gateway retired. Use Paystack.']);
    }
    public function down(): void { /* Historical payment decisions are retained. */ }
};
