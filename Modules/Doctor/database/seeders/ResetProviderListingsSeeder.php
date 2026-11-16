<?php

namespace Modules\Doctor\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Doctor\app\Models\Clinic;
use Modules\Doctor\app\Models\ClinicSubscription;
use Modules\Doctor\app\Models\Department;
use Modules\Doctor\app\Models\Doctor;
use Modules\Doctor\app\Models\DoctorSubscription;
use Modules\Doctor\app\Models\DoctorTranslation;
use Modules\Doctor\app\Models\Facility;
use Modules\Doctor\app\Models\Location;
use Modules\Doctor\app\Models\SubscriptionPackage;

class ResetProviderListingsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            DoctorSubscription::query()->delete();
            ClinicSubscription::query()->delete();
            DB::table('prescribes')->delete();
            DB::table('advice')->delete();
            DB::table('appointments')->delete();
            DB::table('meeting_histories')->delete();
            DB::table('zoom_meetings')->delete();
            DB::table('zoom_credentials')->delete();
            DB::table('schedules')->delete();
            DB::table('leaves')->delete();
            DB::table('withdraw_requests')->delete();
            Facility::query()->delete();
            Clinic::query()->delete();
            Doctor::query()->delete();

            $location = Location::whereHas('translations', fn ($query) => $query->where('name', 'Nairobi'))->first()
                ?? Location::firstOrFail();
            $departments = Department::query()->limit(2)->get();

            if ($departments->count() < 2) {
                throw new \RuntimeException('At least two specialties are required before resetting providers.');
            }

            $doctorPlans = SubscriptionPackage::where('provider_type', 'doctor')->where('is_active', true)->orderBy('price')->take(2)->get();
            $clinicPlans = SubscriptionPackage::where('provider_type', 'clinic')->where('is_active', true)->orderBy('price')->take(2)->get();

            if ($doctorPlans->count() < 2 || $clinicPlans->count() < 2) {
                throw new \RuntimeException('At least two active doctor and clinic subscription plans are required.');
            }

            foreach ([
                ['name' => 'Dr. Amina Wanjiku', 'email' => 'amina.wanjiku@medfind.test', 'phone' => '+254700100001'],
                ['name' => 'Dr. Brian Otieno', 'email' => 'brian.otieno@medfind.test', 'phone' => '+254700100002'],
            ] as $index => $data) {
                $doctor = Doctor::create([
                    ...$data, 'department_id' => $departments[$index]->id, 'location_id' => $location->id,
                    'slug' => strtolower(str_replace(['Dr. ', ' '], ['', '-'], $data['name'])), 'password' => Hash::make('Doctor@1234'),
                    'fee' => 2500, 'years_of_experience' => '8 years', 'status' => 1, 'show_homepage' => 1, 'email_verified_at' => now(),
                ]);
                DoctorTranslation::create(['doctor_id' => $doctor->id, 'lang_code' => 'en', 'designations' => 'Medical Specialist', 'about' => 'Approved Nairobi provider listing.', 'address' => 'Nairobi, Kenya']);
                $plan = $doctorPlans[$index];
                DoctorSubscription::create(['doctor_id' => $doctor->id, 'package_name' => $plan->name, 'plan_code' => $plan->code, 'package_price' => $plan->price, 'plan_duration' => $plan->duration_months, 'entitlements' => $plan->entitlements, 'payment_method' => 'mpesa_manual', 'transaction_code' => 'MFS-DR-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT), 'status' => 'approved', 'approved_at' => now(), 'expires_at' => now()->addMonths($plan->duration_months)]);
            }

            foreach ([
                ['name' => 'Nairobi Family Care', 'email' => 'familycare@medfind.test', 'specialty' => 'Family Medicine', 'location' => 'Westlands, Nairobi'],
                ['name' => 'Nairobi Women\'s Health Centre', 'email' => 'womenshealth@medfind.test', 'specialty' => 'Women\'s Health', 'location' => 'Upper Hill, Nairobi'],
            ] as $index => $data) {
                $clinic = Clinic::create([
                    ...$data, 'slug' => strtolower(str_replace([' ', "'"], ['-', ''], $data['name'])), 'phone' => '+25470020000' . ($index + 1),
                    'consultation_fee' => $index === 0 ? 1500 : 2500,
                    'password' => Hash::make('Clinic@1234'), 'description' => 'Approved Nairobi facility listing.', 'status' => 1, 'email_verified_at' => now(),
                ]);
                $plan = $clinicPlans[$index];
                ClinicSubscription::create(['clinic_id' => $clinic->id, 'package_name' => $plan->name, 'plan_code' => $plan->code, 'package_price' => $plan->price, 'plan_duration' => $plan->duration_months, 'entitlements' => $plan->entitlements, 'payment_method' => 'mpesa_manual', 'transaction_code' => 'MFS-CL-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT), 'status' => 'approved', 'approved_at' => now(), 'expires_at' => now()->addMonths($plan->duration_months)]);
            }
        });
    }
}
