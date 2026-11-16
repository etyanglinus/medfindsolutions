<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Doctor\database\seeders\DepartmentSeeder;
use Modules\Doctor\database\seeders\DoctorServiceSeeder;
use Modules\Doctor\database\seeders\InsuranceProviderSeeder;
use Modules\Doctor\database\seeders\LicensingBodySeeder;

class KenyaHealthcareReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LicensingBodySeeder::class,
            InsuranceProviderSeeder::class,
            DoctorServiceSeeder::class,
            DepartmentSeeder::class,
        ]);
    }
}

