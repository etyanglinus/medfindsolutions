<?php

namespace App\Console\Commands;

use App\Models\ProviderPaymentTransaction;
use App\Services\ProviderPaystackService;
use Illuminate\Console\Command;

class ReconcilePaystackPayments extends Command
{
    protected $signature = 'paystack:reconcile {reference? : Check a single MFS-... reference} {--days=7 : How far back to look for unfinished attempts}';
    protected $description = 'Re-check unfinished Paystack payments and activate any that Paystack reports as paid';

    public function handle(ProviderPaystackService $paystack): int
    {
        $query = ProviderPaymentTransaction::where('gateway', 'paystack');
        $this->argument('reference')
            ? $query->where('gateway_reference', $this->argument('reference'))
            : $query->whereIn('status', ['initiated', 'pending'])->where('created_at', '>=', now()->subDays((int) $this->option('days')));

        $checked = 0;
        foreach ($query->orderBy('id')->get() as $payment) {
            try {
                $status = $paystack->reconcile($payment);
                $payment->refresh();
                $this->line("{$payment->gateway_reference}: Paystack={$status}, local={$payment->status}");
            } catch (\Throwable $e) {
                report($e);
                $this->error("{$payment->gateway_reference}: ".$e->getMessage());
            }
            $checked++;
        }
        $this->info("Checked {$checked} payment(s).");

        // Retry "payment received" emails that could not be sent (e.g. mail server was down).
        $resent = 0;
        ProviderPaymentTransaction::with('subscription')->where('status', 'paid')->where('paid_at', '>=', now()->subDays((int) $this->option('days')))
            ->get()->filter(fn ($payment) => \App\Jobs\SendProviderPaymentConfirmation::shouldSend($payment))
            ->each(function ($payment) use (&$resent) { (new \App\Jobs\SendProviderPaymentConfirmation($payment->id))->handle(); $resent++; });
        if ($resent) $this->info("Retried {$resent} confirmation email(s).");
        return self::SUCCESS;
    }
}
