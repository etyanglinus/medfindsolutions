<?php

namespace App\Services;

use App\Models\ProviderProfile;
use App\Support\KenyaPlaces;
use Illuminate\Database\Eloquent\Builder;
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

    public function doctors(): Builder
    {
        return Doctor::query()->publiclyVisible()->whereHas('subscriptions', fn ($q) => $q->where('status', 'approved')
            ->where(fn ($w) => $w->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->whereJsonContains('entitlements->search_visibility', true));
    }

    public function clinics(): Builder
    {
        return Clinic::query()->where('status', 1)->whereHas('subscriptions', fn ($q) => $q->where('status', 'approved')
            ->where(fn ($w) => $w->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(fn ($w) => $w->whereJsonContains('entitlements->search_visibility', true)->orWhereJsonContains('entitlements->contact', true)->orWhereNull('entitlements')->orWhereJsonLength('entitlements', 0)));
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
