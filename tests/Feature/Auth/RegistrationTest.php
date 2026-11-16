<?php

namespace Tests\Feature\Auth;

use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    public function test_patient_registration_is_disabled(): void
    {
        $this->assertFalse(Route::has('register'));
    }

    public function test_patient_registration_submission_is_disabled(): void
    {
        $this->assertFalse(Route::has('patient-register'));
        $this->assertGuest();
    }
}
