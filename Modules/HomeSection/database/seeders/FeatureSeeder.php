<?php

namespace Modules\HomeSection\database\seeders;

use Illuminate\Database\Seeder;
use Modules\HomeSection\app\Models\Feature;
use Modules\HomeSection\app\Models\FeatureTranslation;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dummyFeatures = [
            [
                'image'        => 'uploads/website-images/dummy/featur-1.webp',
                'icon'         => 'uploads/website-images/dummy/heart.png',
                'translations' => [
                    ['lang_code' => 'en', 'title' => 'Operation Theater', 'description' => 'We have modern and well furnished operation theatre in the city'],
                    ['lang_code' => 'ar', 'title' => 'غرفة العمليات', 'description' => 'لدينا غرفة عمليات حديثة ومجهزة تجهيزاً جيداً في المدينة'],
                ],
            ],
            [
                'image'        => 'uploads/website-images/dummy/featur-2.webp',
                'icon'         => 'uploads/website-images/dummy/ambulance.png',
                'translations' => [
                    ['lang_code' => 'en', 'title' => 'Emergency Services', 'description' => 'If you need any kind of emergency services, we are always available'],
                    ['lang_code' => 'ar', 'title' => 'خدمات الطوارئ', 'description' => 'إذا كنت بحاجة إلى أي نوع من خدمات الطوارئ، نحن دائماً متاحون'],
                ],
            ],
            [
                'image'        => 'uploads/website-images/dummy/featur-3.webp',
                'icon'         => 'uploads/website-images/dummy/doctor.png',
                'translations' => [
                    ['lang_code' => 'en', 'title' => 'Qualified Doctors', 'description' => 'We have the best qualified doctors to serve our valuable patients'],
                    ['lang_code' => 'ar', 'title' => 'أطباء مؤهلون', 'description' => 'لدينا أفضل الأطباء المؤهلين لخدمة مرضانا القيمين'],
                ],
            ],
        ];

        foreach ($dummyFeatures as $item) {
            // Insert feature
            $feature = Feature::create([
                'image'      => $item['image'],
                'icon'       => $item['icon'],
            ]);

            // Insert feature translations
            foreach ($item['translations'] as $translation) {
                FeatureTranslation::create([
                    'feature_id'  => $feature->id,
                    'lang_code'   => $translation['lang_code'],
                    'title'       => $translation['title'],
                    'description' => $translation['description'],
                ]);
            }
        }
    }
}
