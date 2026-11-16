<?php

namespace App\Console\Commands;

use App\Models\ProviderSubscription;
use Illuminate\Console\Command;

class ExpireProviderSubscriptions extends Command
{
    protected $signature = 'providers:expire-subscriptions';
    protected $description = 'Mark elapsed shared provider subscriptions as expired';

    public function handle(): int
    {
        $count = ProviderSubscription::query()
            ->where('status', ProviderSubscription::STATUS_APPROVED)
            ->whereNotNull('expires_at')->where('expires_at', '<=', now())
            ->update(['status' => ProviderSubscription::STATUS_EXPIRED, 'updated_at' => now()]);
        $this->info("Expired {$count} provider subscription(s).");
        return self::SUCCESS;
    }
}
