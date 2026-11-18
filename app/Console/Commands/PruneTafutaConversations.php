<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneTafutaConversations extends Command
{
    protected $signature = 'tafuta:prune {--days=7}';
    protected $description = 'Delete Tafuta chats with no message for 7 days (privacy)';

    public function handle(): int
    {
        $deleted = DB::table('tafuta_conversations')->where('updated_at', '<', now()->subDays(max(1, (int) $this->option('days'))))->delete();
        $this->info("Deleted {$deleted} old Tafuta conversations.");
        return self::SUCCESS;
    }
}
