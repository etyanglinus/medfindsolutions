<?php

namespace App\Console\Commands;

use App\Jobs\ActivateAdminClient;
use App\Models\ProviderPaymentTransaction;
use Illuminate\Console\Command;

class RetryClientActivation extends Command
{
    protected $signature = 'clients:retry-activation';
    protected $description = 'Retry delivery of paid admin client login emails';
    public function handle(): int
    {
        ProviderPaymentTransaction::where('status', 'paid')->whereHas('subscription', fn ($query) => $query
            ->where('status', 'approved')->where('metadata->admin_onboarding', true)->whereNull('metadata->login_email_sent_at'))
            ->chunkById(100, function ($payments) {
                foreach ($payments as $payment) {
                    try { (new ActivateAdminClient($payment->id))->handle(); }
                    catch (\Throwable $exception) { report($exception); }
                }
            });
        return self::SUCCESS;
    }
}
