<?php

namespace App\Console\Commands;

use App\Mail\ProviderMonthlyReport;
use App\Services\ProviderEmailPreferences;
use App\Services\ProviderMonthlyReportService;
use App\Services\ProviderRenewalLinks;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends each provider last month's listing performance. Scheduled hourly on the 1st to 3rd of the
 * month with a per-run limit, so a large mailing stays under the host's hourly email cap; every
 * provider is emailed once per month (provider_monthly_reports), and a failed send is retried on
 * the next run.
 */
class SendProviderMonthlyReports extends Command
{
    protected $signature = 'providers:send-monthly-report
        {--month= : Month to report on, YYYY-MM (default: last month)}
        {--limit=100 : Maximum emails to send in this run}
        {--dry-run : List who would be emailed, send nothing}';

    protected $description = 'Email providers their monthly listing performance with a Renew now link';

    public function handle(ProviderMonthlyReportService $reports, ProviderRenewalLinks $links): int
    {
        $period = $this->option('month') ?: now()->subMonthNoOverflow()->format('Y-m');
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period)) {
            $this->error('--month must look like 2026-10');
            return self::FAILURE;
        }
        $monthLabel = CarbonImmutable::createFromFormat('!Y-m', $period)->format('F Y');
        $limit = max(1, (int) $this->option('limit'));
        $sent = $skipped = $failed = $optedOut = 0;
        $optOuts = app(ProviderEmailPreferences::class)->monthlyReportOptOuts();

        foreach ($reports->recipients() as $r) {
            if ($sent >= $limit) break;
            ['type' => $type, 'client' => $client] = $r;
            if (isset($optOuts[$type.':'.$client->id])) { $optedOut++; continue; }
            $email = ProviderRenewalLinks::email($client);
            if (!$email) { $skipped++; continue; }

            $already = DB::table('provider_monthly_reports')->where(['provider_type' => $type, 'provider_id' => $client->id, 'period' => $period])->whereNotNull('sent_at')->exists();
            if ($already) { $skipped++; continue; }

            $totals = $reports->totals($type, $client, $period);
            $expiresAt = $r['expires_at'];
            $expired = $expiresAt && $expiresAt->isPast();
            $renewalDue = !$expired && $expiresAt && $expiresAt->lte(now()->addDays(30));

            if ($this->option('dry-run')) {
                $this->line(sprintf('%-8s #%-6d %-40s views=%d search=%d %s', $type, $client->id, $email, $totals['profile_view']['current'], $totals['search_appearance']['current'], $expired ? '(expired)' : ($renewalDue ? '(renewal due)' : '')));
                $sent++;
                continue;
            }

            try {
                Mail::to($email)->send(new ProviderMonthlyReport(
                    $client->name, $monthLabel, $totals, $r['plan'], $expiresAt?->format('d M Y'),
                    $renewalDue, $expired, $links->url($type, $client), route('login', ['type' => $type]),
                    ProviderEmailPreferences::unsubscribeUrl($type, $client->id),
                ));
                DB::table('provider_monthly_reports')->updateOrInsert(
                    ['provider_type' => $type, 'provider_id' => $client->id, 'period' => $period],
                    ['totals' => json_encode($totals), 'sent_at' => now(), 'updated_at' => now(), 'created_at' => now()],
                );
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('Monthly report email failed', ['type' => $type, 'id' => $client->id, 'error' => $e->getMessage()]);
            }
        }

        $this->info(sprintf('%s report for %s: %d %s, %d skipped, %d unsubscribed, %d failed.', $this->option('dry-run') ? 'Dry run of' : 'Sent', $monthLabel, $sent, $this->option('dry-run') ? 'would be sent' : 'sent', $skipped, $optedOut, $failed));
        return self::SUCCESS;
    }
}
