<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

/**
 * Opt-outs for optional provider emails. Only the monthly performance report can be turned off;
 * payment, verification, licence and renewal emails are about the account and always go out.
 */
class ProviderEmailPreferences
{
    public function wantsMonthlyReport(string $type, int $id): bool
    {
        $value = DB::table('provider_email_preferences')->where(['provider_type' => $type, 'provider_id' => $id])->value('monthly_report');
        return $value === null || (bool) $value;
    }

    public function setMonthlyReport(string $type, int $id, bool $on): void
    {
        DB::table('provider_email_preferences')->updateOrInsert(
            ['provider_type' => $type, 'provider_id' => $id],
            ['monthly_report' => $on, 'monthly_report_changed_at' => now(), 'updated_at' => now(), 'created_at' => now()],
        );
    }

    /** "type:id" keys of everyone who turned the monthly report off. */
    public function monthlyReportOptOuts(): array
    {
        return DB::table('provider_email_preferences')->where('monthly_report', false)->get(['provider_type', 'provider_id'])
            ->mapWithKeys(fn ($row) => [$row->provider_type.':'.$row->provider_id => true])->all();
    }

    /** Link in the email footer; works without signing in and does not expire. */
    public static function unsubscribeUrl(string $type, int $id): string
    {
        return URL::signedRoute('provider.email-preferences', ['type' => $type, 'id' => $id]);
    }
}
