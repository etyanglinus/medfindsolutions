<?php

namespace Tests\Feature;

use App\Services\LicenceVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifiedBadgeTest extends TestCase
{
    use RefreshDatabase;

    private function document(int $providerId, string $status, ?string $expires): void
    {
        \Modules\Doctor\app\Models\ProviderDocument::create(['provider_type' => 'doctor', 'provider_id' => $providerId, 'licensing_body' => 'KMPDC', 'verification_body' => 'KMPDC', 'registration_number' => 'R1', 'accreditation_expires_at' => $expires, 'file_path' => 'docs/x.pdf', 'status' => $status]);
    }

    public function test_badge_only_for_an_approved_current_licence(): void
    {
        $this->document(1, 'approved', now()->addYear()->toDateString()); // verified
        $this->document(2, 'approved', null);                              // verified, no expiry
        $this->document(3, 'approved', now()->subDay()->toDateString());  // expired
        $this->document(4, 'pending', null);                               // not reviewed
        $this->document(5, 'rejected', null);

        $licences = app(LicenceVerification::class);
        $this->assertSame([true, true, false, false, false, false], array_map(fn ($id) => $licences->isVerified('doctor', $id), [1, 2, 3, 4, 5, 6]));
        $this->assertFalse($licences->isVerified('clinic', 1), 'A doctor document does not verify a facility with the same id.');
        $this->assertFalse($licences->isVerified('doctor', null));

        $card = view('patient.partials.verified-badge', ['type' => 'doctor', 'id' => 1])->render();
        $this->assertStringContainsString('Verified', $card);
        $this->assertStringContainsString('Licence verified by MedFind', view('patient.partials.verified-badge', ['type' => 'doctor', 'id' => 2, 'full' => true])->render());
        $this->assertSame('', trim(view('patient.partials.verified-badge', ['type' => 'doctor', 'id' => 3])->render()));
    }
}
