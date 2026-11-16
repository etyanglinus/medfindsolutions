<?php

namespace App\Http\Controllers\Patient;

use Illuminate\Http\Request;
use Modules\Day\app\Models\Day;
use Modules\Leave\app\Models\Leave;
use App\Http\Controllers\Controller;
use Modules\Doctor\app\Models\Doctor;
use Gloudemans\Shoppingcart\Facades\Cart;
use Modules\Doctor\app\Models\Department;
use Modules\Schedule\app\Models\Schedule;

class AppointmentController extends Controller {
    public function getAppointment(Request $request) {
        $leave = Leave::where(['doctor_id' => $request?->doctor_id, 'date' => $request?->date])->count();
        $html = "";
        if ($leave == 0) {
            $doctor_id = $request->doctor_id;
            $doctor = Doctor::active()
                ->whereHas('subscriptions', function ($query) {
                    $query->where('status', 'approved')
                        ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                        ->whereJsonContains('entitlements->search_visibility', true);
                })
                ->find($doctor_id);
            if (! $doctor) {
                return response()->json(['error' => __('This doctor is not currently available for appointments.')]);
            }
            $day_name = date('l', strtotime($request->date));
            $day = Day::where('slug', strtolower($day_name))->first();
            $schedules = Schedule::where(['doctor_id' => $doctor_id, 'day_id' => $day?->id])->get();
            if ($schedules->count() != 0) {
                foreach ($schedules as $index => $schedule) {
                    $html .= '<option value="' . $schedule->id . '">' . strtoupper($schedule->start_time) . '-' . strtoupper($schedule->end_time) . '</option>';
                }
                return response()->json(['success' => $html]);
            } else {
                $html = "<h4 class='text-danger'>" . __('Schedule Not Found') . "</h4>";
                return response()->json(['error' => $html]);
            }
        } else {
            $html = "<h4 class='text-danger'>" . __('Doctor is unavailable on the selected date') . "</h4>";
            return response()->json(['error' => $html]);
        }
    }

    public function getDepartmentDoctor($id) {
        $doctors = Doctor::where(['department_id' => $id])->active()->verify()->paid()
            ->whereHas('subscriptions', function ($query) {
                $query->where('status', 'approved')
                    ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                    ->whereJsonContains('entitlements->search_visibility', true);
            })
            ->get();
        $html = '<option value="">' . __('Doctor') . '</option>';
        if ($doctors) {
            foreach ($doctors as $doctor) {
                $html .= '<option value="' . $doctor?->id . '">' . ucfirst($doctor?->name) . '</option>';
            }
        }
        return response()->json($html);
    }

    public function createAppointment(Request $request) {
        $doctor_id = $request->doctor_id;
        $department_id = $request->department_id;
        $date = $request->date;
        $schedule_id = $request->schedule_id;

        $doctor = Doctor::active()
            ->whereHas('subscriptions', function ($query) {
                $query->where('status', 'approved')
                    ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                    ->whereJsonContains('entitlements->search_visibility', true);
            })
            ->findOrFail($doctor_id);
        $schedule = Schedule::where('doctor_id', $doctor->id)->findOrFail($schedule_id);
        $department = Department::find($department_id);

        $options['doctor_id'] = $doctor_id;
        $options['department_id'] = $department?->id;
        $options['location_id'] = $doctor?->location?->id;
        $options['date'] = $date;
        $options['time'] = $schedule?->start_time . '-' . $schedule?->end_time;
        $options['schedule_id'] = $schedule?->id;
        $options['day_id'] = $schedule?->day_id;
        
        Cart::add(date('ymdis'), $doctor?->name, 1, $doctor?->fee, 0, $options);

        $notification = ['message' => __('Appointment Created Successfully'), 'alert-type' => 'success'];
        return to_route('patient.payment')->with($notification);
    }

    public function removeAppointment($id) {
        Cart::remove($id);
        $notification = ['message' => __('Deleted Successfully'), 'alert-type' => 'success'];
        return back()->with($notification);
    }
}
