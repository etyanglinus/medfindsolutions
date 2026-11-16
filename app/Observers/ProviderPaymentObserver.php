<?php

namespace App\Observers;

use App\Jobs\ActivateAdminClient;
use App\Models\ProviderPaymentTransaction;

class ProviderPaymentObserver
{
    public function saved(ProviderPaymentTransaction $payment): void
    {
        if ($payment->status === 'paid' && $payment->wasChanged('status') && data_get($payment->subscription?->metadata, 'admin_onboarding')) {
            ActivateAdminClient::dispatch($payment->id)->afterCommit();
        }
    }
}
