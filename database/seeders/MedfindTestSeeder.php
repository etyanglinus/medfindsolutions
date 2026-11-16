<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use App\Models\UserDetails;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Doctor\app\Enums\DoctorStatus;
use Modules\Doctor\app\Models\Clinic;
use Modules\Doctor\app\Models\ClinicSubscription;
use Modules\Doctor\app\Models\Doctor;
use Modules\Doctor\app\Models\DoctorSubscription;
use Modules\Doctor\app\Models\Department;
use Modules\Doctor\app\Models\Location;
use Spatie\Permission\Models\Role;

/**
 * MedfindTestSeeder
 *
 * Seeds one test account per user type for local development.
 * Safe to run multiple times — uses updateOrCreate / firstOrCreate throughout.
 *
 * Test credentials:
 * ─────────────────────────────────────────────────────────
 *  Role      | Email                        | Password
 * ─────────────────────────────────────────────────────────
 *  Admin     | admin@medfind.test           | Admin@1234
 *  Doctor    | doctor@medfind.test          | Doctor@1234
 *  Clinic    | clinic@medfind.test          | Clinic@1234
 *  Customer  | customer@medfind.test        | Customer@1234
 * ─────────────────────────────────────────────────────────
 */
class MedfindTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdmin();
        $this->seedCustomer();
        $this->seedDoctor();
        $this->seedClinic();
    }

    // ── Admin ────────────────────────────────────────────────────────────────

    private function seedAdmin(): void
    {
        if (Admin::where('email', 'admin@medfind.test')->exists()) {
            return;
        }

        $admin = new Admin();
        $admin->name          = 'MedFind Admin';
        $admin->email         = 'admin@medfind.test';
        $admin->image         = 'uploads/website-images/admin.jpg';
        $admin->password      = Hash::make('Admin@1234');
        $admin->is_super_admin = true;
        $admin->status        = 'active';
        $admin->save();

        $role = Role::first();
        if ($role) {
            $admin->assignRole($role);
        }
    }

    // ── Customer ─────────────────────────────────────────────────────────────

    private function seedCustomer(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'customer@medfind.test'],
            [
                'patient_id'            => 'MF-CUST-001',
                'name'                  => 'Test Customer',
                'password'              => Hash::make('Customer@1234'),
                'status'                => 'active',
                'is_banned'             => 'no',
                'ready_for_appointment' => 1,
                'email_verified_at'     => now(),
            ]
        );

        UserDetails::updateOrCreate(
            ['user_id' => $user->id],
            [
                'phone'          => '+254 700 000 001',
                'address'        => '123 Test Street, Nairobi',
                'city'           => 'Nairobi',
                'country'        => 'Kenya',
                'age'            => '30',
                'occupation'     => 'Software Engineer',
                'gender'         => 'male',
                'date_of_birth'  => '1994-01-15',
                'weight'         => '75kg',
                'guardian_name'  => 'Test Guardian',
                'guardian_phone' => '+254 700 000 009',
            ]
        );
    }

    // ── Doctor ───────────────────────────────────────────────────────────────

    private function seedDoctor(): void
    {
        // Fallback to first available department / location if no specific ones exist
        $department = Department::first();
        $location   = Location::first();

        $doctor = Doctor::updateOrCreate(
            ['email' => 'doctor@medfind.test'],
            [
                'name'               => 'Dr. Test Doctor',
                'slug'               => 'dr-test-doctor',
                'phone'              => '+254 700 000 002',
                'fee'                => 1000,
                'department_id'      => $department?->id ?? 1,
                'location_id'        => $location?->id ?? 1,
                'password'           => Hash::make('Doctor@1234'),
                'status'             => DoctorStatus::ACTIVE->value,
                'show_homepage'      => 1,
                'email_verified_at'  => now(),
                'registration_token' => Str::random(60),
            ]
        );

        // Attach an approved Premium subscription so the doctor is fully active
        DoctorSubscription::updateOrCreate(
            ['doctor_id' => $doctor->id],
            [
                'package_name'     => 'Premium',
                'package_price'    => 3500.00,
                'plan_duration'    => 3,
                'payment_method'   => 'paystack',
                'transaction_code' => 'PS_TEST_' . strtoupper(Str::random(10)),
                'status'           => 'approved',
                'approved_at'      => now(),
                'admin_note'       => 'Seeded test account — auto-approved.',
            ]
        );
    }

    // ── Clinic ───────────────────────────────────────────────────────────────

    private function seedClinic(): void
    {
        $clinic = Clinic::updateOrCreate(
            ['email' => 'clinic@medfind.test'],
            [
                'name'               => 'Test Clinic & Diagnostics',
                'slug'               => 'test-clinic-diagnostics',
                'specialty'          => 'General Practice, Diagnostics',
                'phone'              => '+254 700 000 003',
                'whatsapp'           => '+254 700 000 003',
                'location'           => 'Nairobi CBD, Kenya',
                'description'        => 'A test clinic account for local development. Covers general practice and diagnostic services.',
                'operating_hours'    => 'Mon–Fri 8am–6pm, Sat 9am–1pm',
                'insurance_accepted' => 'NHIF, AAR, Jubilee Health',
                'status'             => 1,
                'password'           => Hash::make('Clinic@1234'),
                'registration_token' => Str::random(60),
                'email_verified_at'  => now(),
            ]
        );

        // Attach an approved Executive subscription
        ClinicSubscription::updateOrCreate(
            ['clinic_id' => $clinic->id],
            [
                'package_name'     => 'Executive',
                'package_price'    => 15000.00,
                'plan_duration'    => 6,
                'payment_method'   => 'paystack',
                'transaction_code' => 'PS_TEST_' . strtoupper(Str::random(10)),
                'status'           => 'approved',
                'approved_at'      => now(),
                'admin_note'       => 'Seeded test account — auto-approved.',
            ]
        );
    }
}
