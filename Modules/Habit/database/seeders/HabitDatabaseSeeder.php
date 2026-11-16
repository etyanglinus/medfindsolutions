<?php

namespace Modules\Habit\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Habit\app\Models\Habit;
use Modules\Habit\app\Models\HabitTranslation;

class HabitDatabaseSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        // Insert Habits
        $habits = [
            ['id' => 1, 'status' => 1],
            ['id' => 2, 'status' => 1],
            ['id' => 3, 'status' => 1],
        ];

        Habit::insert($habits);

        // Insert Habit Translations
        $translations = [
            ['id' => 1, 'habit_id' => 1, 'lang_code' => 'en', 'name' => 'Walking'],
            ['id' => 2, 'habit_id' => 1, 'lang_code' => 'ar', 'name' => 'المشي'],
            ['id' => 3, 'habit_id' => 2, 'lang_code' => 'en', 'name' => 'Smoking'],
            ['id' => 4, 'habit_id' => 2, 'lang_code' => 'ar', 'name' => 'التدخين'],
            ['id' => 5, 'habit_id' => 3, 'lang_code' => 'en', 'name' => 'Alcohol'],
            ['id' => 6, 'habit_id' => 3, 'lang_code' => 'ar', 'name' => 'الكحول'],
        ];

        HabitTranslation::insert($translations);
    }
}
