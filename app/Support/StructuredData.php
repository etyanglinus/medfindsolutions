<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * schema.org JSON-LD for public pages, so search engines can show a provider's name, specialty,
 * area and star rating in results. Only facts already shown on the page are included; contact
 * numbers are left out (they are behind plan entitlements and click tracking).
 */
class StructuredData
{
    public static function physician($doctor, $reviews = null): array
    {
        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => 'Physician',
            'name' => $doctor->name,
            'url' => route('website.doctor.details', $doctor->slug),
            'image' => $doctor->image ? url($doctor->image) : null,
            'medicalSpecialty' => $doctor->department?->name,
            'description' => self::text($doctor->about ?? null),
            'address' => self::address($doctor->address ?? $doctor->location?->name),
            'aggregateRating' => self::rating($reviews),
        ]);
    }

    public static function clinic($clinic, $reviews = null): array
    {
        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => 'MedicalClinic',
            'name' => $clinic->name,
            'url' => route('website.facility.details', $clinic->slug),
            'image' => $clinic->logo ? self::upload($clinic->logo) : null,
            'medicalSpecialty' => $clinic->specialty,
            'description' => self::text($clinic->description),
            'address' => self::address($clinic->address ?: $clinic->location),
            'aggregateRating' => self::rating($reviews),
        ]);
    }

    public static function supplier($supplier): array
    {
        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $supplier->name,
            'url' => route('website.medical-supplier', $supplier->slug),
            'logo' => $supplier->logo ? self::upload($supplier->logo) : null,
            'description' => self::text($supplier->description),
            'address' => self::address($supplier->address),
            'areaServed' => collect((array) $supplier->counties_served)->filter()->map(fn ($c) => ['@type' => 'AdministrativeArea', 'name' => $c.' County'])->values()->all() ?: null,
        ]);
    }

    public static function school($school, $courses = null): array
    {
        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => 'EducationalOrganization',
            'name' => $school->name,
            'url' => route('website.healthcare-institution', $school->slug),
            'logo' => $school->logo ? self::upload($school->logo) : null,
            'description' => self::text($school->about ?? null),
            'address' => self::address($school->location),
            'hasCourse' => collect($courses)->map(fn ($c) => self::course($c, $school, false))->values()->all() ?: null,
        ]);
    }

    public static function course($course, $school, bool $withContext = true): array
    {
        return self::clean([
            '@context' => $withContext ? 'https://schema.org' : null,
            '@type' => 'Course',
            'name' => $course->name,
            'description' => self::text($course->description) ?? $course->name,
            'url' => route('website.healthcare-course', $course->slug),
            'provider' => ['@type' => 'EducationalOrganization', 'name' => $school->name, 'url' => route('website.healthcare-institution', $school->slug)],
        ]);
    }

    /** @param array<int, array{name: string, url: string}> $crumbs */
    public static function breadcrumbs(array $crumbs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($crumbs)->values()->map(fn ($c, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c['name'], 'item' => $c['url']])->all(),
        ];
    }

    /** @param iterable<array{name: string, url: string}> $items */
    public static function itemList(string $name, iterable $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $name,
            'itemListElement' => collect($items)->values()->map(fn ($item, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $item['name'], 'url' => $item['url']])->all(),
        ];
    }

    private static function rating($reviews): ?array
    {
        $reviews = collect($reviews)->filter(fn ($r) => (int) ($r->rating ?? 0) > 0);
        if ($reviews->isEmpty()) return null;
        return ['@type' => 'AggregateRating', 'ratingValue' => round($reviews->avg('rating'), 1), 'reviewCount' => $reviews->count(), 'bestRating' => 5, 'worstRating' => 1];
    }

    private static function address(?string $place): ?array
    {
        $place = trim(strip_tags((string) $place));
        return $place === '' ? null : ['@type' => 'PostalAddress', 'streetAddress' => Str::limit($place, 200, ''), 'addressCountry' => 'KE'];
    }

    private static function text(?string $html): ?string
    {
        $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES)));
        return $text === '' ? null : Str::limit($text, 300);
    }

    /** Provider uploads live on the public disk (public/uploads/store); template images are site-relative. */
    private static function upload(string $path): string
    {
        return preg_match('#^(partner-|supplier-|clinic-gallery)#', $path) ? asset('uploads/store/'.$path) : asset($path);
    }

    private static function clean(array $data): array
    {
        return array_filter($data, fn ($v) => $v !== null && $v !== '' && $v !== []);
    }
}
