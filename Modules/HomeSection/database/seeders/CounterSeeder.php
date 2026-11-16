<?php

namespace Modules\HomeSection\database\seeders;

use Illuminate\Database\Seeder;
use Modules\HomeSection\app\Models\Counter;
use Modules\HomeSection\app\Models\CounterTranslation;

class CounterSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run() {
        $counters = [
            [
                'icon'         => 'uploads/website-images/dummy/patient.png',
                'qty'          => '500',
                'translations' => [
                    ['lang_code' => 'en', 'title' => 'Patients'],
                    ['lang_code' => 'ar', 'title' => 'المرضى'],
                ],
            ],
            [
                'icon'         => 'uploads/website-images/dummy/hospital.png',
                'qty'          => '16',
                'translations' => [
                    ['lang_code' => 'en', 'title' => 'Departments'],
                    ['lang_code' => 'ar', 'title' => 'الأقسام'],
                ],
            ],
            [
                'icon'         => 'uploads/website-images/dummy/medical-team.png',
                'qty'          => '50',
                'translations' => [
                    ['lang_code' => 'en', 'title' => 'Expert Doctors'],
                    ['lang_code' => 'ar', 'title' => 'الأطباء الخبراء'],
                ],
            ],
            [
                'icon'         => 'uploads/website-images/dummy/medical-lab.png',
                'qty'          => '120',
                'translations' => [
                    ['lang_code' => 'en', 'title' => 'Total Labs'],
                    ['lang_code' => 'ar', 'title' => 'إجمالي المختبرات'],
                ],
            ],
        ];

        foreach ($counters as $counterData) {
            // Create the counter
            $counter = Counter::create([
                'icon' => $counterData['icon'],
                'qty'  => $counterData['qty'],
            ]);

            // Create translations
            foreach ($counterData['translations'] as $translation) {
                CounterTranslation::create([
                    'counter_id' => $counter->id,
                    'lang_code'  => $translation['lang_code'],
                    'title'      => $translation['title'],
                ]);
            }
        }
    }
}
