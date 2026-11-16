<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\ProviderProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Doctor\app\Models\Clinic;
use Modules\Doctor\app\Models\ClinicSubscription;
use Modules\Doctor\app\Models\Department;
use Modules\Doctor\app\Models\DepartmentTranslation;
use Modules\Doctor\app\Models\Doctor;
use Modules\Doctor\app\Models\DoctorSubscription;
use Modules\Doctor\app\Models\DoctorTranslation;
use Modules\Doctor\app\Models\Facility;
use Modules\Doctor\app\Models\FacilityTranslation;
use Modules\Doctor\app\Models\Location;
use Modules\Doctor\app\Models\LocationTranslation;
use Modules\HealthcareInstitution\app\Models\HealthcareInstitution;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;
use Spatie\Permission\Models\Role;

class KenyaDemoAccountsSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'NairobiDemo!2026';

    private array $specialties = [
        'Cardiology', 'Paediatrics', 'Obstetrics and Gynaecology', 'Dermatology', 'Orthopaedics',
        'Neurology', 'Ophthalmology', 'General Surgery', 'Psychiatry', 'Family Medicine',
    ];

    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->seedAdmin();

        $location = $this->nairobiLocation();
        $departments = collect($this->specialties)->mapWithKeys(fn (string $name) => [$name => $this->department($name)]);

        $specialists = [
            ['Amina Wanjiku', 'Cardiology', 'Upper Hill'], ['Kevin Ochieng', 'Paediatrics', 'Westlands'],
            ['Faith Njeri', 'Obstetrics and Gynaecology', 'Kilimani'], ['Brian Kiptoo', 'Dermatology', 'Parklands'],
            ['Mercy Atieno', 'Orthopaedics', 'South C'], ['Daniel Mwangi', 'Neurology', 'Lavington'],
            ['Lilian Chebet', 'Ophthalmology', 'Nairobi CBD'], ['Samuel Otieno', 'General Surgery', 'Langata'],
            ['Joy Muthoni', 'Psychiatry', 'Karen'], ['Peter Kamau', 'Family Medicine', 'Roysambu'],
        ];

        foreach ($specialists as $index => [$name, $specialty, $area]) {
            $number = $index + 1;
            $email = sprintf('specialist%02d@medfind.test', $number);
            $doctor = Doctor::updateOrCreate(['email' => $email], [
                'department_id' => $departments[$specialty]->id,
                'location_id' => $location->id,
                'name' => 'Dr. ' . $name . ' (Demo)',
                'slug' => 'demo-specialist-' . str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'phone' => '+254700600' . str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                'fee' => 1500 + ($number * 100),
                'years_of_experience' => (string) (4 + ($number % 12)),
                'image' => 'uploads/website-images/dummy/doctor-' . $number . '.webp',
                'status' => 1,
                'show_homepage' => 1,
                'email_verified_at' => now(),
                'password' => Hash::make(self::DEMO_PASSWORD),
            ]);
            DoctorTranslation::updateOrCreate(['doctor_id' => $doctor->id, 'lang_code' => 'en'], [
                'designations' => $specialty . ' · MBChB',
                'licensing_body' => 'Kenya Medical Practitioners and Dentists Council (Demo)',
                'about' => 'Demonstration specialist profile serving patients in ' . $area . ', Nairobi.',
                'address' => $area . ', Nairobi, Kenya',
                'educations' => '<p>MBChB, University of Nairobi (Demo profile)</p>',
                'experience' => '<p>Kenya-based clinical experience (Demo profile).</p>',
                'qualifications' => '<p>Sample listing for staging and demonstration only.</p>',
                'seo_title' => 'Dr. ' . $name . ' · ' . $specialty . ' · Demo',
                'seo_description' => 'Demonstration specialist listing in Nairobi.',
            ]);
            DoctorSubscription::updateOrCreate(['doctor_id' => $doctor->id, 'transaction_code' => 'DEMO-SP-' . $number], [
                'package_name' => 'Demo Basic', 'plan_code' => 'basic', 'package_price' => 0,
                'plan_duration' => 12, 'entitlements' => ['search_visibility' => true, 'contact' => true],
                'payment_method' => 'demo', 'bank_name' => 'Demo data', 'account_number' => 'DEMO',
                'routing_number' => 'DEMO', 'branch' => 'Nairobi', 'status' => 'approved',
                'approved_at' => now(), 'expires_at' => now()->addYear(), 'admin_note' => 'Seeded staging demonstration account.',
            ]);
        }

        $facilities = [
            ['Umoja Community Health Centre', 'General and Family Medicine', 'Umoja'],
            ['Karura Specialist Hospital', 'Specialist Hospital', 'Parklands'],
            ['Kilimani Women and Children Centre', 'Maternity and Paediatrics', 'Kilimani'],
            ['South C Medical Centre', 'Outpatient and Diagnostics', 'South C'],
            ['Westlands Family Hospital', 'Family Medicine', 'Westlands'],
            ['Langata Orthopaedic Centre', 'Orthopaedics', 'Langata'],
            ['Roysambu Medical Pavilion', 'Outpatient Services', 'Roysambu'],
            ['Lavington Wellness Hospital', 'Mental and Community Health', 'Lavington'],
            ['Embakasi Surgical Centre', 'General Surgery', 'Embakasi'],
            ['Ngara Eye and Dental Centre', 'Eye and Dental Care', 'Ngara'],
        ];

        foreach ($facilities as $index => [$name, $specialty, $area]) {
            $number = $index + 1;
            $email = sprintf('clinic%02d@medfind.test', $number);
            $clinic = Clinic::updateOrCreate(['email' => $email], [
                'name' => $name . ' (Demo)', 'slug' => 'demo-clinic-' . str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'specialty' => $specialty, 'phone' => '+254700610' . str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                'location' => $area . ', Nairobi', 'description' => 'Demonstration facility account for staging.',
                'status' => 1, 'password' => Hash::make(self::DEMO_PASSWORD), 'email_verified_at' => now(),
            ]);
            ClinicSubscription::updateOrCreate(['clinic_id' => $clinic->id, 'transaction_code' => 'DEMO-CL-' . $number], [
                'package_name' => 'Demo Basic', 'plan_code' => 'basic', 'package_price' => 0,
                'plan_duration' => 12, 'entitlements' => [], 'payment_method' => 'demo',
                'bank_name' => 'Demo data', 'account_number' => 'DEMO', 'routing_number' => 'DEMO',
                'branch' => 'Nairobi', 'status' => 'approved', 'approved_at' => now(),
                'expires_at' => now()->addYear(), 'admin_note' => 'Seeded staging demonstration account.',
            ]);

            $facilitySlug = 'demo-facility-' . str_pad((string) $number, 2, '0', STR_PAD_LEFT);
            $facility = Facility::updateOrCreate(['slug' => $facilitySlug], [
                'department_id' => $departments['Family Medicine']->id, 'location_id' => $location->id,
                'name' => $name . ' (Directory Demo)', 'email' => $email, 'slug' => $facilitySlug,
                'phone' => $clinic->phone, 'fee' => 0, 'status' => 1, 'show_homepage' => 1,
                'image' => 'uploads/website-images/dummy/facility-' . (($number - 1) % 5 + 1) . '.webp',
            ]);
            FacilityTranslation::updateOrCreate(['facility_id' => $facility->id, 'lang_code' => 'en'], [
                'licensing_body' => 'Demo facility profile',
                'about' => 'Directory demonstration paired with the clinic portal account ' . $email . '.',
                'address' => $area . ', Nairobi, Kenya', 'seo_title' => $name . ' (Demo)',
                'seo_description' => 'Staging directory entry in ' . $area . ', Nairobi.',
            ]);
        }

        $schools = [
            ['Nairobi Institute of Nursing', 'Nairobi West'], ['Eastlands College of Health Sciences', 'Donholm'],
            ['Kilimani School of Clinical Practice', 'Kilimani'], ['Parklands Medical Training Institute', 'Parklands'],
            ['Kasarani College of Nursing', 'Kasarani'], ['Karen Allied Health Institute', 'Karen'],
            ['Embakasi College of Community Health', 'Embakasi'], ['Westlands Institute of Medical Studies', 'Westlands'],
            ['Umoja College of Health Professions', 'Umoja'], ['Langata School of Nursing and Midwifery', 'Langata'],
        ];
        foreach ($schools as $index => [$name, $area]) {
            $number = $index + 1;
            $email = sprintf('school%02d@medfind.test', $number);
            $existingSchool = HealthcareInstitution::where('official_email', $email)->first();
            $school = HealthcareInstitution::updateOrCreate(['official_email' => $email], [
                'name' => $name . ' (Demo)', 'slug' => 'demo-school-' . str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'institution_type' => 'Health training college', 'ownership' => 'private',
                'phone' => '+254700620' . str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                'contact_person' => 'Demo Admissions Office', 'location' => $area . ', Nairobi, Kenya',
                'about' => 'Demonstration healthcare education provider for staging.',
                'password' => Hash::make(self::DEMO_PASSWORD), 'registration_token' => $existingSchool?->registration_token ?: Str::random(80),
                'email_verified_at' => now(), 'email_verified_token' => null,
            ]);
            $profile = ProviderProfile::updateOrCreate(['provider_type' => ProviderProfile::TYPE_SCHOOL, 'provider_id' => $school->id], [
                'status' => ProviderProfile::STATUS_PENDING_APPROVAL,
                'verification_status' => ProviderProfile::VERIFICATION_UNVERIFIED,
            ]);
            if ($school->provider_profile_id !== $profile->id) $school->update(['provider_profile_id' => $profile->id]);
        }

        $suppliers = [
            ['AfyaMed Supplies', 'Medical equipment and diagnostics'], ['Twiga Clinical Products', 'Clinical consumables'],
            ['Jirani Healthcare Technologies', 'Laboratory and imaging equipment'], ['Baraka Surgical Solutions', 'Surgical instruments'],
            ['Imara Hospital Products', 'Hospital furniture and equipment'], ['Mwangaza Diagnostics Kenya', 'Diagnostic equipment'],
            ['Nairobi Care Medical Distributors', 'Medical supplies distribution'], ['Pamoja Pharma and Devices', 'Pharmaceutical and medical devices'],
            ['Uhuru Laboratory Supplies', 'Laboratory consumables'], ['Kifaru Mobility and Rehab', 'Rehabilitation equipment'],
        ];
        foreach ($suppliers as $index => [$name, $specialty]) {
            $number = $index + 1;
            $email = sprintf('supplier%02d@medfind.test', $number);
            $existingSupplier = MedicalSupplier::where('official_email', $email)->first();
            $supplier = MedicalSupplier::updateOrCreate(['official_email' => $email], [
                'name' => $name . ' (Demo)', 'slug' => 'demo-supplier-' . str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'phone' => '+254700630' . str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                'whatsapp' => '+254700630' . str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                'website' => 'https://example.test', 'description' => $specialty . ' supplier demonstration profile for staging.',
                'year_established' => 2010 + $number, 'address' => 'Industrial Area, Nairobi, Kenya',
                'counties_served' => ['Nairobi', 'Kiambu', 'Machakos'],
                'supplier_types' => [$specialty], 'brands' => ['DemoCare', 'AfyaPro'],
                'services' => ['Delivery in Nairobi', 'Product support'], 'password' => Hash::make(self::DEMO_PASSWORD),
                'registration_token' => $existingSupplier?->registration_token ?: Str::random(80),
                'email_verified_at' => now(), 'email_verified_token' => null,
            ]);
            $profile = ProviderProfile::updateOrCreate(['provider_type' => ProviderProfile::TYPE_SUPPLIER, 'provider_id' => $supplier->id], [
                'status' => ProviderProfile::STATUS_PENDING_APPROVAL,
                'verification_status' => ProviderProfile::VERIFICATION_UNVERIFIED,
            ]);
            if ($supplier->provider_profile_id !== $profile->id) $supplier->update(['provider_profile_id' => $profile->id]);
        }
    }

    private function seedAdmin(): void
    {
        $password = env('DEMO_ADMIN_PASSWORD');
        if (! $password) throw new \RuntimeException('Set DEMO_ADMIN_PASSWORD in the current process before running this seeder.');

        $admin = Admin::updateOrCreate(['email' => 'admin@medfindsolutions.co.ke'], [
            'name' => 'MedFind Administrator', 'password' => Hash::make($password),
            'status' => 'active', 'is_super_admin' => true,
        ]);
        $role = Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first();
        if ($role && ! $admin->hasRole($role)) $admin->assignRole($role);
    }

    private function nairobiLocation(): Location
    {
        $location = Location::whereHas('translations', fn ($query) => $query->where('lang_code', 'en')->where('name', 'Nairobi'))->first();
        if (! $location) {
            $location = Location::create(['status' => 1]);
            LocationTranslation::create(['location_id' => $location->id, 'lang_code' => 'en', 'name' => 'Nairobi']);
        }
        return $location;
    }

    private function department(string $name): Department
    {
        $department = Department::whereHas('translations', fn ($query) => $query->where('lang_code', 'en')->where('name', $name))->first();
        if (! $department) {
            $department = Department::create(['slug' => Str::slug($name), 'status' => 1, 'show_homepage' => 1]);
            DepartmentTranslation::create(['department_id' => $department->id, 'lang_code' => 'en', 'name' => $name, 'description' => $name . ' demo listings in Nairobi.']);
        }
        return $department;
    }

}
