<?php

namespace App\Services;

use App\Models\ProviderProfile;
use App\Support\KenyaPlaces;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Doctor\app\Models\Clinic;
use Modules\Doctor\app\Models\Department;
use Modules\Doctor\app\Models\Doctor;
use Modules\HealthcareInstitution\app\Models\HealthcareInstitution;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;

/**
 * Who is publicly listed, by type, using the same rules as the directory pages, plus a small
 * cached index (id => searchable place text) that decides which SEO landing pages have content.
 * Landing pages, the sitemap and the "browse by" link blocks all read from here, so a page is
 * linked and listed in the sitemap only when it actually shows providers.
 */
class PublicDirectory
{
    public const TYPES = ['doctor', 'clinic', 'supplier', 'school'];
    private const CACHE_MINUTES = 10;

    /**
     * THE rule for "this specialist / facility is publicly listed", used by the home page, the
     * directories, profile pages, landing pages and the sitemap, so they always agree:
     * the account is switched on, it has an approved plan that has not expired and that does not
     * exclude search listing, the profile has a photo and a description (never a placeholder page),
     * and (only when the admin switch is on) an approved, current licence.
     * Confirming the email is NOT required to be listed: paying proves the inbox, and sign-in
     * enforces confirmation separately.
     */
    public function listed(string $type, Builder $query): Builder
    {
        abort_unless(in_array($type, ['doctor', 'clinic'], true), 500);
        $table = $query->getModel()->getTable();
        $photo = $type === 'doctor' ? 'image' : 'logo';
        return app(LicenceVerification::class)->apply($query, $type)
            ->where("$table.status", 1)
            // No placeholder listings: a photo and a description must be in place before people see it.
            ->whereNotNull("$table.$photo")->where("$table.$photo", '!=', '')
            ->when($type === 'doctor', fn ($q) => $q->whereHas('translations', fn ($t) => $t->whereNotNull('about')->where('about', '!=', '')))
            ->when($type === 'clinic', fn ($q) => $q->whereNotNull("$table.description")->where("$table.description", '!=', ''))
            ->whereHas('subscriptions', fn ($q) => $q->where('status', 'approved')
                ->where(fn ($w) => $w->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->where(fn ($w) => $w->whereJsonContains('entitlements->search_visibility', true)->orWhereJsonContains('entitlements->contact', true)
                    ->orWhereNull('entitlements')->orWhereJsonLength('entitlements', 0)));
    }

    public function doctors(): Builder
    {
        return $this->listed('doctor', Doctor::query());
    }

    public function clinics(): Builder
    {
        return $this->listed('clinic', Clinic::query());
    }

    public function suppliers(): Builder
    {
        $portal = app(PartnerPortalService::class);
        $ids = ProviderProfile::query()->where('provider_type', ProviderProfile::TYPE_SUPPLIER)
            ->where('status', ProviderProfile::STATUS_PUBLISHED)->whereNotNull('published_at')
            ->whereHas('subscriptions', fn ($q) => $q->where('status', 'approved')->where(fn ($s) => $s->whereNull('starts_at')->orWhere('starts_at', '<=', now()))->where(fn ($s) => $s->whereNull('expires_at')->orWhere('expires_at', '>', now())))
            ->with('subscriptions')->get()
            ->filter(fn ($p) => $portal->allows($p, 'search_visibility') && $portal->allows($p, 'supplier_profile'))
            ->pluck('id');
        return MedicalSupplier::query()->whereIn('provider_profile_id', $ids);
    }

    public function schools(): Builder
    {
        return HealthcareInstitution::query()->published();
    }

    /**
     * For the admin lists: is this provider on the public site, and if not, why not?
     *
     * @return array{listed: bool, reasons: list<string>, url: ?string, plan: ?string, expires: ?\Carbon\CarbonInterface, expired: bool}
     */
    public function status(string $type, Model $client): array
    {
        $listed = $this->query($type)->whereKey($client->getKey())->exists();
        $reasons = [];
        $profile = in_array($type, ['school', 'supplier'], true)
            ? ProviderProfile::where('provider_type', $type)->where('provider_id', $client->getKey())->first() : null;

        if (in_array($type, ['doctor', 'clinic'], true)) {
            if ((int) $client->status !== 1) $reasons[] = __('Account is switched off');
            $subscriptions = $client->subscriptions()->get();
        } else {
            if (!$profile) $reasons[] = __('No plan chosen yet');
            elseif ($profile->status === ProviderProfile::STATUS_SUSPENDED) $reasons[] = __('Suspended');
            elseif ($profile->status !== ProviderProfile::STATUS_PUBLISHED || !$profile->published_at) $reasons[] = __('Not published yet');
            $subscriptions = $profile ? $profile->subscriptions()->get() : collect();
        }

        $current = $subscriptions->filter(fn ($s) => $s->status === 'approved' && (!$s->expires_at || $s->expires_at->isFuture()) && (!isset($s->starts_at) || !$s->starts_at || $s->starts_at->lte(now())))
            ->sortByDesc(fn ($s) => $s->expires_at?->timestamp ?? PHP_INT_MAX)->first();
        $lastApproved = $subscriptions->where('status', 'approved')->sortByDesc(fn ($s) => $s->expires_at?->timestamp ?? PHP_INT_MAX)->first();
        if (!$current) {
            if ($lastApproved?->expires_at) $reasons[] = __('Plan expired on :date', ['date' => $lastApproved->expires_at->format('d M Y')]);
            elseif ($subscriptions->whereIn('status', ['pending', 'pending_payment'])->isNotEmpty()) $reasons[] = __('Payment not completed');
            elseif ($profile || in_array($type, ['doctor', 'clinic'], true)) $reasons[] = __('No paid plan');
        }
        if ($type === 'doctor' && (!filled($client->image) || !$client->translations()->whereNotNull('about')->where('about', '!=', '')->exists())) $reasons[] = __('Profile not completed (photo or description missing)');
        if ($type === 'clinic' && (!filled($client->logo) || !filled($client->description))) $reasons[] = __('Profile not completed (logo or description missing)');
        if (in_array($type, ['doctor', 'clinic'], true) && app(LicenceVerification::class)->required() && !app(LicenceVerification::class)->hasValidLicence($type, $client->getKey())) {
            $reasons[] = __('Licence not approved (the "verified providers only" switch is on)');
        }
        if (!$listed && !$reasons) $reasons[] = __('Plan does not include a public listing');

        $url = $listed ? match ($type) {
            'doctor' => route('website.doctor.details', $client->slug), 'clinic' => route('website.facility.details', $client->slug),
            'school' => route('website.healthcare-institution', $client->slug), 'supplier' => route('website.medical-supplier', $client->slug),
        } : null;
        $plan = $current ?? $lastApproved;
        return ['listed' => $listed, 'reasons' => $listed ? [] : array_values(array_unique($reasons)), 'url' => $url,
            'plan' => $plan?->package_name, 'expires' => $plan?->expires_at, 'expired' => !$current && (bool) $lastApproved];
    }

    public function query(string $type): Builder
    {
        return match ($type) {
            'doctor' => $this->doctors(), 'clinic' => $this->clinics(), 'supplier' => $this->suppliers(), 'school' => $this->schools(),
        };
    }

    /**
     * id => ['text' => place text, 'department' => department id (doctors only)], cached briefly.
     *
     * @return array<int, array{text: string, department: ?int}>
     */
    public function index(string $type): array
    {
        return Cache::remember("seo.directory-index.$type", now()->addMinutes(self::CACHE_MINUTES), function () use ($type) {
            $rows = [];
            if ($type === 'doctor') {
                foreach ($this->doctors()->with(['translations:id,doctor_id,address', 'location.translations'])->get(['id', 'department_id', 'location_id']) as $d) {
                    $text = $d->translations->pluck('address')->filter()->implode(' ').' '.($d->location?->translations->pluck('name')->implode(' ') ?? '');
                    $rows[$d->id] = ['text' => $text, 'department' => $d->department_id];
                }
            } elseif ($type === 'clinic') {
                foreach ($this->clinics()->get(['id', 'location', 'address']) as $c) $rows[$c->id] = ['text' => trim($c->location.' '.$c->address), 'department' => null];
            } elseif ($type === 'supplier') {
                foreach ($this->suppliers()->get(['id', 'address', 'counties_served']) as $s) $rows[$s->id] = ['text' => trim(implode(' ', (array) $s->counties_served).' '.$s->address), 'department' => null];
            } else {
                foreach ($this->schools()->get(['id', 'location']) as $s) $rows[$s->id] = ['text' => (string) $s->location, 'department' => null];
            }
            return $rows;
        });
    }

    /** Ids of listed providers of $type in $place (and, for doctors, in $departmentId). */
    public function ids(string $type, ?string $place = null, ?int $departmentId = null): Collection
    {
        return collect($this->index($type))
            ->filter(fn ($row) => ($departmentId === null || $row['department'] === $departmentId) && ($place === null || KenyaPlaces::mentions($row['text'], $place)))
            ->keys();
    }

    /** @return array<string, array{name: string, count: int}> place slug => name and provider count, busiest first */
    public function places(string $type, ?int $departmentId = null): array
    {
        $candidates = $type === 'supplier' ? KenyaPlaces::counties() : KenyaPlaces::all();
        $out = [];
        foreach ($candidates as $slug => $name) {
            $count = $this->ids($type, $name, $departmentId)->count();
            if ($count > 0) $out[$slug] = ['name' => $name, 'count' => $count];
        }
        uasort($out, fn ($a, $b) => [$b['count'], $a['name']] <=> [$a['count'], $b['name']]);
        return $out;
    }

    /** Specialties that have at least one listed specialist: slug => [department, count], biggest first. */
    public function specialties(): Collection
    {
        $counts = collect($this->index('doctor'))->countBy('department');
        return Department::query()->whereIn('id', $counts->keys())->with('translation')->get()
            ->filter(fn ($d) => filled($d->name) && filled($d->slug)) // a specialty without a name in this language gets no page
            ->map(fn ($d) => ['department' => $d, 'count' => $counts[$d->id] ?? 0, 'slug' => $d->slug])
            ->sortBy([['count', 'desc'], [fn ($row) => $row['department']->name, 'asc']])
            ->keyBy('slug');
    }

    /** "Cardiologists", "Cardiology specialists", "Ear, Nose & Throat (ENT) specialists" */
    public static function specialtyPlural(string $name): string
    {
        $name = trim($name);
        if (preg_match('/(ist|ian|eon|geon)$/i', $name)) return $name.'s';
        return $name.' specialists';
    }

    public static function forget(): void
    {
        foreach (self::TYPES as $type) Cache::forget("seo.directory-index.$type");
    }
}
