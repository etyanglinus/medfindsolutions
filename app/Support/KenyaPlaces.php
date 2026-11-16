<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Places used for "… in <place>" landing pages: Kenya's 47 counties plus the Nairobi
 * neighbourhoods people search for. A page exists only when at least one live provider's
 * address, location or service area mentions the place (see PublicDirectory).
 */
class KenyaPlaces
{
    public const COUNTIES = [
        'Mombasa', 'Kwale', 'Kilifi', 'Tana River', 'Lamu', 'Taita Taveta', 'Garissa', 'Wajir', 'Mandera', 'Marsabit',
        'Isiolo', 'Meru', 'Tharaka Nithi', 'Embu', 'Kitui', 'Machakos', 'Makueni', 'Nyandarua', 'Nyeri', 'Kirinyaga',
        "Murang'a", 'Kiambu', 'Turkana', 'West Pokot', 'Samburu', 'Trans Nzoia', 'Uasin Gishu', 'Elgeyo Marakwet', 'Nandi', 'Baringo',
        'Laikipia', 'Nakuru', 'Narok', 'Kajiado', 'Kericho', 'Bomet', 'Kakamega', 'Vihiga', 'Bungoma', 'Busia',
        'Siaya', 'Kisumu', 'Homa Bay', 'Migori', 'Kisii', 'Nyamira', 'Nairobi',
    ];

    /** Nairobi neighbourhoods and estates that appear in provider addresses and in searches. */
    public const NAIROBI_AREAS = [
        'Westlands', 'Kilimani', 'Parklands', 'Upper Hill', 'Karen', 'Langata', 'Lavington', 'Kileleshwa', 'Hurlingham',
        'South C', 'South B', 'Nairobi West', 'Embakasi', 'Donholm', 'Umoja', 'Kasarani', 'Roysambu', 'Ngara', 'Eastleigh',
        'Industrial Area', 'Gigiri', 'Runda', 'Ruaka', 'Kitengela', 'Rongai', 'Thika Road',
    ];

    /** @return array<string, string> slug => display name, counties first */
    public static function all(): array
    {
        $out = [];
        foreach (array_merge(self::COUNTIES, self::NAIROBI_AREAS) as $name) $out[Str::slug($name)] = $name;
        return $out;
    }

    /** @return array<string, string> counties only (suppliers declare the counties they serve) */
    public static function counties(): array
    {
        $out = [];
        foreach (self::COUNTIES as $name) $out[Str::slug($name)] = $name;
        return $out;
    }

    public static function name(string $slug): ?string
    {
        return self::all()[$slug] ?? null;
    }

    /** Whole-word, case-insensitive match of a place name inside free text ("Westlands, Nairobi"). */
    public static function mentions(string $text, string $place): bool
    {
        return (bool) preg_match('/(?<![\p{L}])'.preg_quote(mb_strtolower($place), '/').'(?![\p{L}])/u', mb_strtolower($text));
    }
}
