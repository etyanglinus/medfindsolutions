<?php

namespace Modules\Doctor\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Doctor\app\Models\Clinic;
use Modules\Doctor\app\Models\ClinicSubscription;
use Modules\Doctor\app\Models\Department;
use Modules\Doctor\app\Models\Facility;
use Modules\Doctor\app\Models\FacilityTranslation;
use Modules\Doctor\app\Models\Location;
use Modules\Doctor\app\Models\Service;
use Modules\Doctor\app\Models\SubscriptionPackage;

class NairobiClinicSeeder extends Seeder
{
    public function run(): void
    {
        $location = Location::whereHas('translations', fn ($query) => $query->where('name', 'Nairobi'))->first()
            ?? Location::firstOrFail();
        $department = Department::firstOrFail();
        $serviceIds = Service::active()->take(3)->pluck('id');

        $clinics = [
            ['name' => 'The Nairobi Hospital', 'slug' => 'the-nairobi-hospital-demo', 'specialty' => 'Multi-specialty Hospital', 'location' => 'Upper Hill, Nairobi', 'plan' => 'basic'],
            ['name' => 'Aga Khan University Hospital, Nairobi', 'slug' => 'aga-khan-university-hospital-nairobi-demo', 'specialty' => 'Specialist & Teaching Hospital', 'location' => 'Parklands, Nairobi', 'plan' => 'premium'],
            ['name' => 'M.P. Shah Hospital', 'slug' => 'mp-shah-hospital-demo', 'specialty' => 'Multi-specialty Hospital', 'location' => 'Westlands, Nairobi', 'plan' => 'executive'],
            ['name' => 'The Karen Hospital', 'slug' => 'the-karen-hospital-demo', 'specialty' => 'Multi-specialty Hospital', 'location' => 'Karen, Nairobi', 'plan' => 'premium'],
            ['name' => 'Coptic Hospital', 'slug' => 'coptic-hospital-demo', 'specialty' => 'General & Specialist Hospital', 'location' => 'Ngong Road, Nairobi', 'plan' => 'basic'],
        ];

        foreach ($clinics as $index => $data) {
            $clinic = Clinic::updateOrCreate(
                ['email' => 'demo.clinic.' . ($index + 1) . '@medfind.test'],
                [
                    'name' => $data['name'], 'slug' => $data['slug'], 'specialty' => $data['specialty'],
                    'phone' => '+2547002000' . ($index + 1), 'location' => $data['location'],
                    'description' => 'Demo subscription account for ' . $data['name'] . '.',
                    'status' => 1, 'password' => Hash::make('Clinic@1234'), 'email_verified_at' => now(),
                ]
            );

            $plan = SubscriptionPackage::where('provider_type', 'clinic')->where('code', $data['plan'])->firstOrFail();
            ClinicSubscription::updateOrCreate(
                ['clinic_id' => $clinic->id],
                [
                    'package_name' => $plan->name, 'plan_code' => $plan->code, 'package_price' => $plan->price,
                    'plan_duration' => $plan->duration_months, 'entitlements' => $plan->entitlements,
                    'payment_method' => 'mpesa_manual', 'bank_name' => 'M-Pesa Demo Payment',
                    'transaction_code' => 'DEMO-NAIROBI-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'status' => 'approved', 'admin_note' => 'Demo payment approved for subscription testing.',
                    'approved_at' => now()->subDays($index + 1), 'expires_at' => now()->addMonths($plan->duration_months),
                ]
            );

            $facility = Facility::updateOrCreate(
                ['email' => 'demo.facility.' . ($index + 1) . '@medfind.test'],
                [
                    'department_id' => $department->id, 'location_id' => $location->id,
                    'name' => $data['name'], 'slug' => $data['slug'], 'phone' => '+2547003000' . ($index + 1),
                    'fee' => 0, 'years_of_experience' => '20+', 'status' => 1, 'show_homepage' => 1,
                    'image' => 'uploads/website-images/dummy/doctor-' . (($index % 5) + 1) . '.webp',
                ]
            );

            FacilityTranslation::updateOrCreate(
                ['facility_id' => $facility->id, 'lang_code' => 'en'],
                [
                    'licensing_body' => 'Demo facility record', 'accepted_insurance' => 'Demo insurance data',
                    'about' => 'Demo facility record for ' . $data['name'] . ' with an approved subscription.',
                    'address' => $data['location'] . ', Kenya', 'seo_title' => $data['name'],
                    'seo_description' => 'Demo listing for ' . $data['name'] . '.',
                ]
            );
            $facility->services()->sync($serviceIds);
        }
    }
}
