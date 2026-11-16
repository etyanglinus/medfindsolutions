<?php

namespace Modules\Doctor\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Doctor\app\Models\Location;
use Modules\Doctor\app\Models\LocationTranslation;

/**
 * KenyaLocationSeeder
 *
 * Adds four Kenyan cities as searchable locations.
 * Locations in this app have NO name column on the locations table itself —
 * the name lives in location_translations (one row per language).
 * That is the "translation pattern" Laravel apps use for multi-language content.
 */
class KenyaLocationSeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            ['en' => 'Nairobi',  'ar' => 'نيروبي'],
            ['en' => 'Mombasa',  'ar' => 'مومباسا'],
            ['en' => 'Kisumu',   'ar' => 'كيسومو'],
            ['en' => 'Nakuru',   'ar' => 'ناكورو'],
        ];

        foreach ($cities as $city) {
            // Create the parent location row (status defaults to active = 1)
            $location = Location::create(['status' => 1]);

            // Create one translation row per language
            foreach (['en', 'ar'] as $lang) {
                LocationTranslation::create([
                    'location_id' => $location->id,
                    'lang_code'   => $lang,
                    'name'        => $city[$lang],
                ]);
            }
        }
    }
}
