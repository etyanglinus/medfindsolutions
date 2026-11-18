<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Sample providers in tests get what a real, complete public profile has: a photo or logo and a
 * description long enough to pass the "no placeholder pages" rule.
 */
trait CompletesProviderProfiles
{
    protected string $sampleAbout = 'We offer friendly, professional care for patients and families, with clear advice and fair, transparent prices.';

    protected function completeDoctor($doctor): void
    {
        $doctor->forceFill(['image' => $doctor->image ?: 'uploads/sample/doctor.jpg'])->save();
        if ($doctor->translations()->exists()) {
            $doctor->translations()->whereNull('about')->orWhere('about', '')->update(['about' => $this->sampleAbout]);
            $doctor->translations()->where('doctor_id', $doctor->id)->get()->each(fn ($t) => mb_strlen((string) $t->about) < 80 ? $t->update(['about' => $this->sampleAbout]) : null);
        } else {
            DB::table('doctor_translations')->insert(['doctor_id' => $doctor->id, 'lang_code' => 'en', 'about' => $this->sampleAbout]);
        }
    }

    protected function completeClinic($clinic): void
    {
        $clinic->forceFill(['logo' => $clinic->logo ?: 'uploads/sample/clinic.jpg', 'description' => mb_strlen((string) $clinic->description) >= 80 ? $clinic->description : $this->sampleAbout])->save();
    }
}
