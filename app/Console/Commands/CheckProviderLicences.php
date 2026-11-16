<?php

namespace App\Console\Commands;

use App\Mail\LicenceExpiryNotice;
use App\Models\ProviderProfile;
use App\Services\LicenceVerification;
use App\Services\ProviderPaystackService;
use App\Services\ProviderRenewalLinks;
use App\Services\PublicDirectory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Doctor\app\Models\ProviderDocument;

/**
 * Daily licence housekeeping:
 *  - 14 days before an approved licence expires, email the provider to upload the renewal;
 *  - once it has expired, mark it "expired". A school or supplier left without a valid licence is
 *    unpublished and set back to unverified; specialists and facilities drop out of the directory
 *    through LicenceVerification when the admin switch is on. The provider is emailed either way.
 */
class CheckProviderLicences extends Command
{
    protected $signature = 'providers:check-licences';
    protected $description = 'Warn providers before their licence expires and unlist expired ones';

    public const WARN_DAYS = 14;

    public function handle(LicenceVerification $licences): int
    {
        $warned = $expired = 0;

        ProviderDocument::where('status', 'approved')->whereDate('accreditation_expires_at', today()->addDays(self::WARN_DAYS))->get()
            ->each(function ($doc) use (&$warned) {
                // Skip when a newer approved document already runs past this one.
                if ($this->hasLaterDocument($doc)) return;
                $warned += $this->notify($doc, false, false);
            });

        ProviderDocument::where('status', 'approved')->whereNotNull('accreditation_expires_at')->whereDate('accreditation_expires_at', '<', today())->get()
            ->each(function ($doc) use ($licences, &$expired) {
                $doc->update(['status' => 'expired', 'admin_note' => trim(($doc->admin_note ? $doc->admin_note.' ' : '').__('Expired on :date.', ['date' => $doc->accreditation_expires_at->format('d M Y')]))]);
                $hidden = false;
                if (!$licences->hasValidLicence($doc->provider_type, $doc->provider_id)) {
                    if (in_array($doc->provider_type, ['school', 'supplier'], true)) {
                        $profile = ProviderProfile::where('provider_type', $doc->provider_type)->where('provider_id', $doc->provider_id)->first();
                        if ($profile) {
                            $hidden = $profile->status === ProviderProfile::STATUS_PUBLISHED;
                            $profile->update(['verification_status' => ProviderProfile::VERIFICATION_UNVERIFIED, 'verified_at' => null]
                                + ($hidden ? ['status' => ProviderProfile::STATUS_APPROVED, 'published_at' => null] : []));
                        }
                    } else {
                        $hidden = $licences->required();
                    }
                    $expired += $this->notify($doc, true, $hidden);
                }
            });

        if ($expired) { PublicDirectory::forget(); Cache::forget('seo.sitemap.xml'); }
        $this->info("Licence check: {$warned} warned, {$expired} expired.");
        return self::SUCCESS;
    }

    private function hasLaterDocument($doc): bool
    {
        return DB::table('provider_documents')->where('provider_type', $doc->provider_type)->where('provider_id', $doc->provider_id)
            ->where('id', '!=', $doc->id)->whereIn('status', ['approved', 'pending'])
            ->where(fn ($w) => $w->whereNull('accreditation_expires_at')->orWhereDate('accreditation_expires_at', '>', $doc->accreditation_expires_at))->exists();
    }

    private function notify($doc, bool $expired, bool $hidden): int
    {
        $client = ProviderPaystackService::model($doc->provider_type)::find($doc->provider_id);
        $email = $client ? ProviderRenewalLinks::email($client) : null;
        if (!$email) return 0;
        $uploadUrl = match ($doc->provider_type) {
            'doctor' => route('doctor.documents.index'), 'clinic' => route('clinic.documents.index'),
            default => route('website.partner.documents.index', ['type' => $doc->provider_type]),
        };
        try {
            Mail::to($email)->send(new LicenceExpiryNotice($client->name, $doc->accreditation_expires_at->format('d M Y'), $expired, $hidden, $uploadUrl));
            return 1;
        } catch (\Throwable $e) {
            Log::warning('Licence notice failed', ['document' => $doc->id, 'error' => $e->getMessage()]);
            return 0;
        }
    }
}
