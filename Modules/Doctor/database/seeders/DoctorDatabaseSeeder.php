<?php

namespace Modules\Doctor\database\seeders;

use Illuminate\Database\Seeder;

class DoctorDatabaseSeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * Order matters — Locations and Departments must exist before Doctors,
     * because the doctors table has foreign keys (department_id, location_id)
     * that reference those tables.
     */
    public function run(): void {
        $this->call([
            SubscriptionPackageSeeder::class,
            NairobiClinicSeeder::class,
            DoctorServiceSeeder::class,
            LocationSeeder::class,       // New York, Chicago, Boston, Los Angeles
            KenyaLocationSeeder::class,  // Nairobi, Mombasa, Kisumu, Nakuru
            DepartmentSeeder::class,     // Neurology, Cardiology, Ophthalmology, etc.
            LicensingBodySeeder::class,  // KMPDC, PPB, KMLTTB, RBK, PCK, OTCK, KNDI, CPB etc.
            InsuranceProviderSeeder::class, // Requested insurance provider bodies
            DoctorInfoSeeder::class,     // 10 original demo doctors
            KenyanDoctorSeeder::class,   // 12 Kenyan doctors
        ]);
    }
}
