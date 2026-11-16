<?php

namespace Modules\Doctor\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Doctor\app\Models\InsuranceProvider;

class InsuranceProviderSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        $providers = [
            'AAR Insurance',
            'APA Insurance',
            'Britam',
            'Cannon Assurance',
            'CIC Insurance Group',
            'Fidelity Shield Insurance',
            'First Assurance Kenya',
            'GA Insurance',
            'Heritage Insurance',
            'ICEA Lion Group',
            'Jubilee Health Insurance',
            'Kenya Re-Insurance Corporation',
            'Kenindia Assurance',
            'Liberty Kenya Holdings',
            'Madison Insurance',
            'Old Mutual Kenya',
            'Pacis Insurance',
            'Resolution Insurance',
            'Sanlam Allianz Kenya',
            'Social Health Authority (SHA)',
            'UAP Old Mutual Insurance',
        ];

        foreach ($providers as $index => $providerName) {
            InsuranceProvider::updateOrCreate(
                ['name' => $providerName],
                ['status' => true, 'sort_order' => $index + 1]
            );
        }
    }
}
