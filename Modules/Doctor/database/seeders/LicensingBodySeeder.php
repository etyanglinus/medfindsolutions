<?php

namespace Modules\Doctor\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Doctor\app\Models\LicensingBody;

class LicensingBodySeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        $doctorBodies = [
            'Kenya Medical Practitioners and Dentists Council (KMPDC)',
            'Pharmacy and Poisons Board (PPB)',
            'Kenya Medical Laboratory Technicians and Technologists Board (KMLTTB)',
            'Radiographers Board of Kenya (RBK)',
            'Physiotherapy Council of Kenya (PCK)',
            'Occupational Therapy Council of Kenya (OTCK)',
            'Kenya Nutritionists and Dieticians Institute (KNDI)',
            'Counsellors and Psychologists Board (CPB)',
        ];

        $facilityBodies = [
            'Kenya Medical Practitioners and Dentists Council (KMPDC)',
            'Pharmacy and Poisons Board (PPB)',
            'Kenya Medical Laboratory Technicians and Technologists Board (KMLTTB)',
            'Kenya Nuclear Regulatory Authority (KNRA)',
            'Kenya National Blood Transfusion and Transplant Service (KNBTTS)',
        ];

        foreach ($doctorBodies as $body) {
            LicensingBody::updateOrCreate([
                'name' => $body,
                'type' => 'doctor',
            ], [
                'status' => true,
            ]);
        }

        foreach ($facilityBodies as $body) {
            LicensingBody::updateOrCreate([
                'name' => $body,
                'type' => 'facility',
            ], [
                'status' => true,
            ]);
        }
    }
}
