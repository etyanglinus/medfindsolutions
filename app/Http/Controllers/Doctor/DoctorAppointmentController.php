<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Modules\Day\app\Models\Day;
use Modules\Schedule\app\Models\Schedule;

/**
 * Specialist working hours. The template's appointment, treatment, prescription and earnings
 * screens that used to live here were removed: MedFind is a directory, not a booking system.
 */
class DoctorAppointmentController extends Controller
{
    public function schedule() {
        $days = Day::select('id')->with([
            'translation' => function ($query) {
                $query->select('day_id', 'title');
            },
        ])->active()->get();
        $schedules = Schedule::select('day_id', 'start_time', 'end_time')->where('doctor_id', doctorAuth()?->id)->active()->get();
        return view('doctor.schedule.index', compact('days', 'schedules'));
    }
}
