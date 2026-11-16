<?php

namespace Modules\Medicine\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Medicine\app\Models\Medicine;
use Modules\Medicine\app\Models\MedicineType;
use Modules\Medicine\app\Models\MedicineTranslation;
use Modules\Medicine\app\Models\MedicineTypeTranslation;

class MedicineDatabaseSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        $medicines = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
            ['id' => 4],
            ['id' => 5],
        ];

        foreach ($medicines as $medicine) {
            Medicine::create($medicine);
        }

        // Insert Medicine Translations (English and Arabic only)
        $translations = [
            ['id' => 1, 'medicine_id' => 1, 'lang_code' => 'en', 'name' => 'Napa'],
            ['id' => 2, 'medicine_id' => 1, 'lang_code' => 'ar', 'name' => 'نابا'],
            ['id' => 3, 'medicine_id' => 2, 'lang_code' => 'en', 'name' => 'Acetaminophen'],
            ['id' => 4, 'medicine_id' => 2, 'lang_code' => 'ar', 'name' => 'أسيتامينوفين'],
            ['id' => 5, 'medicine_id' => 3, 'lang_code' => 'en', 'name' => 'Amoxicillin'],
            ['id' => 6, 'medicine_id' => 3, 'lang_code' => 'ar', 'name' => 'أموكسيسيلين'],
            ['id' => 7, 'medicine_id' => 4, 'lang_code' => 'en', 'name' => 'Omeprazole'],
            ['id' => 8, 'medicine_id' => 4, 'lang_code' => 'ar', 'name' => 'أوميبرازول'],
            ['id' => 9, 'medicine_id' => 5, 'lang_code' => 'en', 'name' => 'Doxycycline'],
            ['id' => 10, 'medicine_id' => 5, 'lang_code' => 'ar', 'name' => 'دوكسيسيكلين'],
        ];

        foreach ($translations as $translation) {
            MedicineTranslation::create($translation);
        }

        $medicineTypes = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ];

        foreach ($medicineTypes as $type) {
            MedicineType::create($type);
        }

        // Insert Medicine Type Translations (English and Arabic only)
        $translations = [
            ['id' => 1, 'medicine_type_id' => 1, 'lang_code' => 'en', 'type' => 'Tab'],
            ['id' => 2, 'medicine_type_id' => 1, 'lang_code' => 'ar', 'type' => 'قرص'],
            ['id' => 3, 'medicine_type_id' => 2, 'lang_code' => 'en', 'type' => 'Cap'],
            ['id' => 4, 'medicine_type_id' => 2, 'lang_code' => 'ar', 'type' => 'كبسولة'],
            ['id' => 5, 'medicine_type_id' => 3, 'lang_code' => 'en', 'type' => 'Syp'],
            ['id' => 6, 'medicine_type_id' => 3, 'lang_code' => 'ar', 'type' => 'شراب'],
        ];

        foreach ($translations as $translation) {
            MedicineTypeTranslation::create($translation);
        }
    }
}
