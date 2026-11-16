<?php

namespace App\Http\Controllers\API\Doctor;

use App\Http\Controllers\Controller;
use App\Models\UserDetails;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Appointment\app\Models\Appointment;
use Modules\Day\app\Models\Day;
use Modules\Doctor\app\Models\Advice;
use Modules\Doctor\app\Models\Prescribe;
use Modules\Habit\app\Models\Habit;
use Modules\Medicine\app\Models\Medicine;
use Modules\Medicine\app\Models\MedicineType;
use Modules\Schedule\app\Models\Schedule;
use Barryvdh\DomPDF\Facade\Pdf;

class DoctorAppointmentController extends Controller {
    protected $dosages;
    public function __construct() {
        $this->dosages = ['0-0-0', '0-0-1', '0-1-0', '0-1-1', '1-0-0', '1-0-1', '1-1-0', '1-1-1'];
    }
    public function dosages(): JsonResponse {
        return response()->json(['status' => 'success', 'data' => $this->dosages], 200);
    }
    public function habits(): JsonResponse {
        $code = strtolower(request()->query('language', 'en'));

        $habits = Habit::select('id')->with(['translations' => function ($q) use ($code) {
            $q->where('lang_code', $code)->select('habit_id', 'name');
        }])->active()->get();
        if ($habits) {
            return response()->json(['status' => 'success', 'data' => $habits], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }
    public function days(): JsonResponse {
        $code = strtolower(request()->query('language', 'en'));
        $days = Day::select('id','slug','status')->with(['translations' => function ($q) use ($code) {
            $q->select('day_id','title')->where('lang_code', $code);
        }])->active()->get();
        if ($days->count()) {
            return response()->json(['status' => 'success', 'data' => $days], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }
    public function times(): JsonResponse {
        $data = ['After Meal','Before Meal'];
        return response()->json(['status' => 'success', 'data' => $data], 200);
    }
    public function medicines(): JsonResponse {
        $code = strtolower(request()->query('language', 'en'));
        $medicines = Medicine::with(['translations' => function ($q) use ($code) {
            $q->where('lang_code', $code);
        }])->active()
            ->whereHas('translations', function ($query) {
                $query->orderBy('name', 'asc');
            })->get();
        if ($medicines) {
            return response()->json(['status' => 'success', 'data' => $medicines], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }
    public function medicineTypes(): JsonResponse {
        $code = strtolower(request()->query('language', 'en'));
        $medicineTypes = MedicineType::with(['translations' => function ($q) use ($code) {
            $q->where('lang_code', $code);
        }])->active()
            ->whereHas('translations', function ($query) {
                $query->orderBy('type', 'asc');
            })->get();
        if ($medicineTypes) {
            return response()->json(['status' => 'success', 'data' => $medicineTypes], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }
    public function todayAppointment(): JsonResponse {
        $doctor = auth()->guard('doctor_api')->user();

        $appointments = Appointment::with(['user:id,name,email,image', 'user.details', 'schedule'])->where(['doctor_id' => $doctor?->id, 'date' => date('Y-m-d')])->paymentSuccess()->notTreated()->latest()->paginate(10);

        if ($appointments) {
            return response()->json(['status' => 'success', 'data' => $appointments], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }
    public function newAppointment(): JsonResponse {
        $doctor = auth()->guard('doctor_api')->user();
        $appointments = Appointment::with(['user:id,name,email,image', 'user.details', 'schedule'])
            ->where('doctor_id', $doctor?->id)->paymentSuccess()->notTreated()->latest()->paginate(10);

        if ($appointments->count()) {
            return response()->json(['status' => 'success', 'data' => $appointments], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }

    public function allAppointment() {
        $doctor = auth()->guard('doctor_api')->user();

        $code = strtolower(request()->query('language', 'en'));
        $habits = Habit::with(['translations' => function ($q) use ($code) {
            $q->where('lang_code', $code)->select('habit_id', 'name');
        }])->active()->get()->keyBy('id');

        $appointments = Appointment::with(['user:id,name,email,image', 'user.details', 'schedule', 'schedule.doctor:id,name,email', 'schedule.day:id', 'schedule.day.translations' => function ($query) use ($code) {
            $query->where('lang_code', $code)->select('day_id', 'title');
        }])->where('doctor_id', $doctor?->id)->latest()->paginate(10);

        $appointments->getCollection()->transform(function ($appointment) use ($habits) {
            $habitIds = json_decode($appointment->habits, true);
            if (is_array($habitIds) && !empty($habitIds)) {
                $habitIds = array_filter($habitIds, function ($id) {
                    return is_int($id) || is_string($id);
                });
                $appointment->habits = $habits->only($habitIds)->pluck('translations.0.name')->toArray();
            } else {
                $appointment->habits = [];
            }

            return $appointment;
        });

        $treated = Appointment::where('doctor_id', $doctor?->id)->paymentSuccess()->treated()->count();
        $notTreated = Appointment::where('doctor_id', $doctor?->id)->paymentSuccess()->notTreated()->count();
        if ($appointments->count()) {
            return response()->json(['status' => 'success', 'data' => ['appointments' => $appointments, 'treated' => $treated, 'notTreated' => $notTreated]], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);

    }
    public function patientOldAppointment($patient_id) {
        $doctor = auth()->guard('doctor_api')->user();

        $appointments = Appointment::with(['doctor:id,name,email,image', 'user:id,name,email,image', 'user.details', 'schedule'])->where(['user_id' => $patient_id, 'doctor_id' => $doctor?->id])->treated()->latest()->paginate(10);

        $code = strtolower(request()->query('language', 'en'));

        $habits = Habit::with(['translations' => function ($q) use ($code) {
            $q->where('lang_code', $code)->select('habit_id', 'name');
        }])->active()->get()->keyBy('id');

        $appointments->getCollection()->transform(function ($appointment) use ($habits) {
            $habitIds = json_decode($appointment->habits, true);
            if (is_array($habitIds) && !empty($habitIds)) {
                $habitIds = array_filter($habitIds, function ($id) {
                    return is_int($id) || is_string($id);
                });
                $appointment->habits = $habits->only($habitIds)->pluck('translations.0.name')->toArray();
            } else {
                $appointment->habits = [];
            }

            return $appointment;
        });

        if ($appointments->count()) {
            return response()->json(['status' => 'success', 'data' => $appointments], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }

    public function treatment(Request $request, $appointment_id): JsonResponse {
        $doctor = auth()->guard('doctor_api')->user();
        $appointment = Appointment::find($appointment_id);

        if (!$appointment) {
            return response()->json(['status' => 'error', 'message' => __('Appointment not found.')], 404);
        }

        if ($appointment->payment_status != 1) {
            return response()->json(['status' => 'error', 'message' => __('Payment is not completed.')], 400);
        }

        if ($appointment->already_treated == 1) {
            return response()->json(['status' => 'error', 'message' => __('This appointment has already been treated.')], 400);
        }

        if ($appointment->doctor_id != $doctor->id) {
            return response()->json(['status' => 'error', 'message' => __('Unauthorized action.')], 403);
        }

        $medicineAvailable = $request->medicine_name[0] ?? null;
        if ($medicineAvailable == null) {
            return response()->json(['status' => 'error', 'message' => __('No medicine provided.')], 400);
        }

        try {
            // Update user details
            UserDetails::where('user_id', $appointment->user_id)->update(['weight' => $request->weight]);

            // Update appointment details
            $appointment->blood_pressure = $request->blood_pressure;
            $appointment->pulse_rate = $request->pulse_rate;
            $appointment->temperature = $request->temperature;
            $appointment->problem_description = $request->problem_description;
            $appointment->habits = $request->habit ? json_encode($request->habit) : null;
            $appointment->already_treated = 1;
            $appointment->save();

            // Insert prescribed medicines
            foreach ($request->medicine_name as $index => $medicine) {
                if ($medicine != null) {
                    Prescribe::create([
                        'appointment_id' => $appointment_id,
                        'medicine_type'  => $request->medicine_type[$index],
                        'medicine_name'  => $request->medicine_name[$index],
                        'dosage'         => $request->dosage[$index],
                        'number_of_day'  => $request->day[$index],
                        'time'           => $request->time[$index],
                    ]);
                }
            }

            // Insert advice section
            Advice::create([
                'appointment_id'   => $appointment_id,
                'test_description' => $request->test_description,
                'advice'           => $request->advice,
            ]);

            return response()->json(['status' => 'success', 'message' => __('Treatment created successfully.')], 201);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => __('An error occurred while processing the treatment.')], 500);
        }
    }
    public function updateTreatment(Request $request, $appointment_id): JsonResponse {
        $doctor = auth()->guard('doctor_api')->user();
        $appointment = Appointment::find($appointment_id);

        if (!$appointment || $appointment->already_treated != 1) {
            return response()->json(['status' => 'error', 'message' => __('Appointment not found or not treated yet.')], 404);
        }

        if ($appointment->doctor_id != $doctor->id) {
            return response()->json(['status' => 'error', 'message' => __('Unauthorized action.')], 403);
        }

        $medicineAvailable = $request->medicine_name[0] ?? null;
        if ($medicineAvailable == null) {
            return response()->json(['status' => 'error', 'message' => __('No medicine provided.')], 400);
        }

        try {
            // Update user details
            UserDetails::where('user_id', $appointment->user_id)->update(['weight' => $request->weight]);

            // Update appointment details
            $appointment->blood_pressure = $request->blood_pressure;
            $appointment->pulse_rate = $request->pulse_rate;
            $appointment->temperature = $request->temperature;
            $appointment->problem_description = $request->problem_description;
            $appointment->habits = $request->habit ? json_encode($request->habit) : null;
            $appointment->save();

            // Delete old medicines
            Prescribe::where('appointment_id', $appointment_id)->delete();

            // Insert new prescribed medicines
            foreach ($request->medicine_name as $index => $medicine) {
                if ($medicine != null) {
                    Prescribe::create([
                        'appointment_id' => $appointment_id,
                        'medicine_type'  => $request->medicine_type[$index],
                        'medicine_name'  => $request->medicine_name[$index],
                        'dosage'         => $request->dosage[$index],
                        'number_of_day'  => $request->day[$index],
                        'time'           => $request->time[$index],
                    ]);
                }
            }

            // Update or create advice section
            Advice::updateOrCreate(
                ['appointment_id' => $appointment_id],
                [
                    'test_description' => $request->test_description,
                    'advice'           => $request->advice,
                ]
            );

            return response()->json(['status' => 'success', 'message' => __('Treatment updated successfully.')], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => __('An error occurred while updating the treatment.')], 500);
        }
    }

    public function deleteAppointmentPrescribe($id): JsonResponse {
        $prescribe = Prescribe::find($id);
        if ($prescribe) {
            $prescribe->delete();
            return response()->json(['status' => 'success', 'message' => __('Deleted Successfully')], 200);
        }
        return response()->json(['status' => 'error', 'message' => __('Prescription not found.')], 404);
    }
    public function prescribes($appointment_id): JsonResponse {
        $prescribes = Prescribe::where('appointment_id',$appointment_id)->get();
        if ($prescribes) {
            return response()->json(['status' => 'success', 'data' => $prescribes], 200);
        }
        return response()->json(['status' => 'error', 'message' => __('Not Found!')], 404);
    }
    function printPrescription($id) {
        $user = auth()->guard('doctor_api')->user();
        if(!$user){
            return response()->json(['status' => 'error', 'message' => __('Unauthorized')], 401);
        }
        $appointment = $user->appointments()->treated()->find($id);

        if ($appointment) {
            $data = [
                'appointment'         => $appointment,
            ];

            $pdf = Pdf::loadView('prescription', $data)->setPaper('a4', 'landscape');
            return $pdf->download('prescription_' . $appointment->id . '.pdf');
        } else {
            return response()->json(['status' => 'error', 'message' => __('No appointment found.')], 404);
        }
    }

    public function paymentHistory(): JsonResponse {
        $currency_code = strtoupper(request()->query('currency', 'KES'));
        $endDate = Carbon::now(); // Current date and time
        $startDate = $endDate->copy()->subDays(30); // Copy and subtract 30 days

        $startDateFormatted = $startDate->format('Y-m-d H:i:s'); // Start of the date range
        $endDateFormatted = $endDate->format('Y-m-d H:i:s'); // End of the date range (including current time)

        $doctor = auth()->guard('doctor_api')->user();
        $query = Appointment::query();
        $query->where('doctor_id', $doctor?->id)->paymentSuccess();

        $payment = $query->whereBetween('created_at', [$startDateFormatted, $endDateFormatted])->sum('appointment_fee_usd');
        $total_appointment = $query->whereBetween('created_at', [$startDateFormatted, $endDateFormatted])->count();

        $appointments = $query->with(['user:id,name,email,image', 'user.details'])->latest()->paginate(10);
        if ($appointments) {
            return response()->json(['status' => 'success', 'data' => [
                'last_thirty_days_total_earning' => apiCurrency($currency_code, $payment ?? 0), //currency()
                'last_thirty_days_total_patient' => $total_appointment ?? 0,
                'appointments'                   => $appointments,
            ]], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);

    }
    public function schedule(): JsonResponse {
        $code = strtolower(request()->query('language', 'en'));
        $doctor = auth()->guard('doctor_api')->user();

        $days = Day::with(['translations' => function ($q) use ($code) {
            $q->where('lang_code', $code)->select('day_id', 'title');
        }])->select('id')->active()->get();

        $schedules = Schedule::select('day_id', 'start_time', 'end_time')
            ->where('doctor_id', $doctor?->id)
            ->active()
            ->get()
            ->groupBy('day_id');

        $scheduleData = $days->map(function ($day) use ($schedules) {
            $times = $schedules->get($day->id);
            if ($times) {
                return [
                    'day'  => $day->translations->first()?->title,
                    'time' => $times->map(function ($time) {
                        return strtoupper($time->start_time) . ' - ' . strtoupper($time->end_time);
                    })->toArray(),
                ];
            }
            return null;
        })->filter()->values();

        if ($scheduleData->isNotEmpty()) {
            return response()->json(['status' => 'success', 'data' => $scheduleData], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'No schedules found.'], 404);
    }

}
