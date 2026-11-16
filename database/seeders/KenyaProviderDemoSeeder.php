<?php

namespace Database\Seeders;

use App\Models\ProviderProfile;
use App\Services\PartnerPortalService;
use App\Services\ProviderOnboardingService;
use App\Services\ProviderPaystackService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Doctor\app\Models\Department;
use Modules\Doctor\app\Models\Location;
use Modules\Doctor\app\Models\ProviderDocument;
use Modules\Doctor\app\Services\SubscriptionAccessService;
use Modules\Doctor\app\Services\SubscriptionPlanService;

/** Explicitly run this seeder; it does not change existing customer accounts. */
class KenyaProviderDemoSeeder extends Seeder
{
    public function run(): void
    {
        $credentials = DB::transaction(function () {
            $rows = [];
            foreach (['doctor', 'clinic', 'school', 'supplier'] as $type) {
                $label = match ($type) { 'doctor' => 'Specialist', 'clinic' => 'Facility', default => ucfirst($type) };
                $email = 'demo.'.strtolower($label).'@medfind.test';
                $model = ProviderPaystackService::model($type);
                $emailField = in_array($type, ['doctor', 'clinic']) ? 'email' : 'official_email';
                if ($model::withoutGlobalScopes()->where($emailField, $email)->exists()) {
                    $this->command?->warn($email.' already exists; left unchanged.');
                    continue;
                }
                $password = Str::password(18, letters: true, numbers: true, symbols: false);
                $client = new $model;
                $values = [
                    'name' => 'MedFind Demo '.$label.' Kenya', 'slug' => 'medfind-demo-'.strtolower($label).'-kenya',
                    $emailField => $email, 'phone' => '+254700000000', 'password' => Hash::make($password),
                    'email_verified_at' => now(), 'registration_token' => Str::random(80),
                ];
                if ($type === 'doctor') {
                    $department = Department::whereHas('translations', fn ($q) => $q->where('name', 'like', '%General%'))->first() ?? Department::first();
                    if (!$department) {
                        $department = Department::create(['slug' => 'demo-general-medicine', 'status' => 1, 'show_homepage' => 0]);
                        $department->translations()->create(['lang_code' => 'en', 'name' => 'General Medicine', 'description' => 'General medicine']);
                    }
                    $location = Location::whereHas('translations', fn ($q) => $q->where('name', 'Nairobi'))->first();
                    if (!$location) {
                        $location = Location::create(['status' => 1]);
                        $location->translations()->create(['lang_code' => 'en', 'name' => 'Nairobi']);
                    }
                    $values += ['department_id' => $department->id, 'location_id' => $location->id,
                        'years_of_experience' => '5', 'fee' => 0, 'status' => 1, 'show_homepage' => 0,
                        'image' => 'uploads/website-images/default-avatar.png'];
                } else {
                    $values += ['logo' => 'uploads/website-images/default-avatar.png'];
                }
                if ($type === 'clinic') $values += [
                    'status' => 1, 'specialty' => 'General healthcare — demo', 'location' => 'Nairobi',
                    'licensing_body' => 'DEMO ONLY — not a licensed facility', 'description' => 'Fictional Kenyan facility for dashboard testing.',
                    'address' => 'Demo address, Nairobi, Kenya', 'educations' => 'Demo team profile',
                    'experience' => 'Demo facility experience', 'qualifications' => 'Demo qualifications — unverified',
                    'years_of_experience' => '5', 'consultation_fee' => 0, 'whatsapp' => '+254700000000',
                ];
                if ($type === 'school') $values += [
                    'institution_type' => 'College', 'ownership' => 'Private', 'location' => 'Nairobi, Kenya',
                    'about' => 'Fictional Kenyan healthcare training institution for dashboard testing. Not an accredited school.',
                    'contact_person' => 'Demo Admissions Team', 'whatsapp' => '+254700000000',
                ];
                if ($type === 'supplier') $values += [
                    'description' => 'Fictional Kenyan medical supplier for dashboard testing.',
                    'address' => 'Demo address, Nairobi, Kenya', 'counties_served' => ['Nairobi'],
                    'contact_person' => 'Demo Supplier Team', 'whatsapp' => '+254700000000',
                ];
                $client->forceFill($values)->save();
                if ($type === 'doctor') $client->translations()->create([
                    'lang_code' => 'en', 'designations' => 'Demo Specialist',
                    'licensing_body' => 'DEMO ONLY — not a licensed practitioner',
                    'about' => 'Fictional specialist profile for testing MedFind in Kenya.',
                    'address' => 'Demo address, Nairobi, Kenya', 'educations' => 'Demo medical education',
                    'experience' => 'Five years — fictional demo data', 'qualifications' => 'Demo qualifications — unverified',
                ]);
                $profile = ProviderProfile::create(['provider_type' => $type, 'provider_id' => $client->id,
                    'status' => ProviderProfile::STATUS_DRAFT, 'verification_status' => ProviderProfile::VERIFICATION_UNVERIFIED]);
                if (in_array($type, ['school', 'supplier'])) $client->update(['provider_profile_id' => $profile->id]);
                $plan = app(SubscriptionPlanService::class)->find($type, 'executive');
                if (!$plan) throw new \RuntimeException('Executive demo plan unavailable: '.$type);
                $profile->subscriptions()->create([
                    'plan_code' => $plan['id'], 'package_name' => $plan['name'].' (Demo)',
                    'amount' => 0, 'currency' => 'KES', 'duration_months' => 6, 'status' => 'approved',
                    'starts_at' => now(), 'approved_at' => now(), 'expires_at' => now()->addMonthsNoOverflow(6),
                    'metadata' => ['source' => 'kenya_demo_seed', 'complimentary' => true, 'entitlements' => $plan['entitlements']],
                ]);
                if (in_array($type, ['doctor', 'clinic'])) $client->subscriptions()->create([
                    'plan_code' => $plan['id'], 'package_name' => $plan['name'].' (Demo)', 'package_price' => 0,
                    'plan_duration' => 6, 'entitlements' => $plan['entitlements'], 'status' => 'approved',
                    'approved_at' => now(), 'expires_at' => now()->addMonthsNoOverflow(6),
                    'admin_note' => 'Complimentary demo access. No payment received or Paystack transaction created.',
                ]);
                $path = 'provider-documents/'.$type.'/'.$client->id.'/demo-not-a-license.pdf';
                Storage::disk('local')->put($path, $this->samplePdf());
                ProviderDocument::create(['provider_type' => $type, 'provider_id' => $client->id,
                    'licensing_body' => 'DEMO ONLY — not a licensing authority', 'registration_number' => 'DEMO-'.strtoupper($type).'-'.$client->id,
                    'file_path' => $path, 'status' => 'pending', 'admin_note' => 'Sample document for testing only; not proof of registration.']);
                if (!Hash::check($password, $client->fresh()->password) || !app(ProviderOnboardingService::class)->status($client->fresh(), $type)['complete']) throw new \RuntimeException('Demo setup validation failed: '.$type);
                $active = in_array($type, ['doctor', 'clinic'])
                    ? app(SubscriptionAccessService::class)->{'active'.ucfirst($type).'Subscription'}($client)
                    : app(PartnerPortalService::class)->subscription($profile);
                if (!$active) throw new \RuntimeException('Demo subscription validation failed: '.$type);
                if (!auth($type)->validate([$emailField => $email, 'password' => $password])) throw new \RuntimeException('Demo login validation failed: '.$type);
                $rows[] = [$label, $email, $password, route('login', ['type' => $type])];
            }
            return $rows;
        });
        $this->command?->table(['Account', 'Email', 'Password', 'Login URL'], $credentials);
        $this->command?->info('Demo accounts validated: credentials, profile completion and six-month access. No emails sent or payment receipts created.');
    }

    private function samplePdf(): string
    {
        $stream = "BT /F1 18 Tf 50 760 Td (MEDFIND DEMO - NOT A LICENSE) Tj ET";
        $objects = ["<< /Type /Catalog /Pages 2 0 R >>", "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>",
            "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>", "<< /Length ".strlen($stream)." >>\nstream\n".$stream."\nendstream"];
        $pdf = "%PDF-1.4\n"; $offsets = [0];
        foreach ($objects as $i => $object) { $offsets[] = strlen($pdf); $pdf .= ($i + 1)." 0 obj\n".$object."\nendobj\n"; }
        $xref = strlen($pdf); $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) $pdf .= sprintf("%010d 00000 n \n", $offset);
        return $pdf."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
    }
}
