<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * "Verified" means the provider has at least one licence/accreditation document an admin
 * approved, and it has not expired. When the admin switch require_verified_listing is on,
 * specialists and facilities appear in the public directory only if verified; schools and
 * suppliers are always published by an admin, who must first verify them.
 */
class LicenceVerification
{
    public function required(): bool
    {
        $setting = Cache::get('setting');
        $value = $setting->require_verified_listing ?? DB::table('settings')->where('key', 'require_verified_listing')->value('value');
        return (string) ($value ?? '0') === '1';
    }

    /** Restrict a public listing query to verified providers, when the switch is on. */
    public function apply(Builder $query, string $type): Builder
    {
        if (!$this->required()) return $query;
        $table = $query->getModel()->getTable();
        return $query->whereExists(fn ($q) => $q->selectRaw('1')->from('provider_documents')
            ->whereColumn('provider_documents.provider_id', "$table.id")
            ->where('provider_documents.provider_type', $type)
            ->where('provider_documents.status', 'approved')
            ->where(fn ($w) => $w->whereNull('provider_documents.accreditation_expires_at')->orWhereDate('provider_documents.accreditation_expires_at', '>=', today())));
    }

    public function hasValidLicence(string $type, int $id): bool
    {
        return DB::table('provider_documents')->where('provider_type', $type)->where('provider_id', $id)->where('status', 'approved')
            ->where(fn ($w) => $w->whereNull('accreditation_expires_at')->orWhereDate('accreditation_expires_at', '>=', today()))->exists();
    }

    public function setRequired(bool $on): void
    {
        DB::table('settings')->updateOrInsert(['key' => 'require_verified_listing'], ['value' => $on ? '1' : '0', 'updated_at' => now()]);
        Cache::forget('setting');
        Cache::rememberForever('setting', fn () => (object) DB::table('settings')->pluck('value', 'key')->all());
        PublicDirectory::forget();
        Cache::forget('seo.sitemap.xml');
    }
}
