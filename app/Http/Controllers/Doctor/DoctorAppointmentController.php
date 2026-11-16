<?php

namespace App\Http\Controllers\Doctor;

use Carbon\Carbon;
use App\Models\User;
use App\Models\UserDetails;
use Illuminate\Http\Request;
use Modules\Day\app\Models\Day;
use Modules\Habit\app\Models\Habit;
use App\Http\Controllers\Controller;
use Modules\Doctor\app\Models\Advice;
use Modules\Doctor\app\Models\Prescribe;
use Modules\Medicine\app\Models\Medicine;
use Modules\Schedule\app\Models\Schedule;
use Modules\Medicine\app\Models\MedicineType;
use Modules\Appointment\app\Models\Appointment;
use Barryvdh\DomPDF\Facade\Pdf;

class DoctorAppointmentController extends Controller {
    protected $dosages;
    public function __construct() {
        $this->dosages = ['0-0-0', '0-0-1', '0-1-0', '0-1-1', '1-0-0', '1-0-1', '1-1-0', '1-1-1'];
    }
    public function todayAppointment(Request $request) {
        $doctor = doctorAuth();
        $day = Day::where('slug', date('l'))->first();
        $schedules = Schedule::where(['doctor_id' => $doctor?->id, 'day_id' => $day->id])->get();

        $query = Appointment::query();
        $query->where(['doctor_id' => $doctor?->id, 'date' => Date('Y-m-d')])->paymentSuccess()->notTreated();

        $query->when($request->filled('schedule_id'), function ($q) use ($request) {
            $q->where('schedule_id', $request->schedule_id);
        });

        $orderBy = $request->filled('order_by') && $request->order_by == 1 ? 'asc' : 'desc';

        if ($request->filled('par-page')) {
            $appointments = $request->get('par-page') == 'all' ? $query->orderBy('id', $orderBy)->get() : $query->orderBy('id', $orderBy)->paginate($request->get('par-page'))->withQueryString();
        } else {
            $appointments = $query->orderBy('id', $orderBy)->paginate(10)->withQueryString();
        }
        return view('doctor.appointment.today', compact('appointments', 'schedules'));
    }
    public function newAppointment(Request $request) {
        $query = Appointment::query();
        $query->where(['doctor_id' => doctorAuth()?->id])->paymentSuccess()->notTreated();

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $from_date = date('Y-m-d', strtotime($request->from_date));
            $to_date = date('Y-m-d', strtotime($request->to_date));
            $query->whereBetween('date', array($from_date, $to_date));
        }

        $orderBy = $request->filled('order_by') && $request->order_by == 1 ? 'asc' : 'desc';

        if ($request->filled('par-page')) {
            $appointments = $request->get('par-page') == 'all' ? $query->orderBy('id', $orderBy)->get() : $query->orderBy('id', $orderBy)->paginate($request->get('par-page'))->withQueryString();
        } else {
            $appointments = $query->orderBy('id', $orderBy)->paginate(10)->withQueryString();
        }
        return view('doctor.appointment.new', compact('appointments'));
    }
    public function allAppointment(Request $request) {
        $query = Appointment::query();
        $query->where('doctor_id', doctorAuth()->id);

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $from_date = date('Y-m-d', strtotime($request->from_date));
            $to_date = date('Y-m-d', strtotime($request->to_date));
            $query->whereBetween('date', array($from_date, $to_date));
        }

        $query->when($request->filled('patient'), function ($q) use ($request) {
            $q->where('user_id', $request->patient);
        });

        $orderBy = $request->filled('order_by') && $request->order_by == 1 ? 'asc' : 'desc';

        if ($request->filled('par-page')) {
            $appointments = $request->get('par-page') == 'all' ? $query->orderBy('id', $orderBy)->get() : $query->orderBy('id', $orderBy)->paginate($request->get('par-page'))->withQueryString();
        } else {
            $appointments = $query->orderBy('date', $orderBy)->paginate(10)->withQueryString();
        }
        $patients = User::select('name','email','id')->whereIn('id', doctorAuth()->appointments()->distinct()->pluck('user_id'))->active()->get();
        return view('doctor.appointment.all', compact('appointments','patients'));
    }
    public function notTreatedAppointments(Request $request) {
        $query = Appointment::query();
        $query->where('doctor_id', doctorAuth()->id)->notTreated();

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $from_date = date('Y-m-d', strtotime($request->from_date));
            $to_date = date('Y-m-d', strtotime($request->to_date));
            $query->whereBetween('date', array($from_date, $to_date));
        }

        $query->when($request->filled('patient'), function ($q) use ($request) {
            $q->where('user_id', $request->patient);
        });

        $orderBy = $request->filled('order_by') && $request->order_by == 1 ? 'asc' : 'desc';

        if ($request->filled('par-page')) {
            $appointments = $request->get('par-page') == 'all' ? $query->orderBy('id', $orderBy)->get() : $query->orderBy('id', $orderBy)->paginate($request->get('par-page'))->withQueryString();
        } else {
            $appointments = $query->orderBy('date', $orderBy)->paginate(10)->withQueryString();
        }
        $patients = User::select('name','email','id')->whereIn('id', doctorAuth()->appointments()->distinct()->pluck('user_id'))->active()->get();
        return view('doctor.appointment.not_treated_appointments', compact('appointments','patients'));
    }
    public function oldAppointment(Request $request, $id) {
        $user_id = $id;
        $query = Appointment::query();
        $query->where('user_id', $id)->treated();

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $from_date = date('Y-m-d', strtotime($request->from_date));
            $to_date = date('Y-m-d', strtotime($request->to_date));
            $query->whereBetween('date', array($from_date, $to_date));
        }

        $orderBy = $request->filled('order_by') && $request->order_by == 1 ? 'asc' : 'desc';

        if ($request->filled('par-page')) {
            $appointments = $request->get('par-page') == 'all' ? $query->orderBy('id', $orderBy)->get() : $query->orderBy('id', $orderBy)->paginate($request->get('par-page'))->withQueryString();
        } else {
            $appointments = $query->orderBy('id', $orderBy)->paginate(10)->withQueryString();
        }
        return view('doctor.appointment.old', compact('appointments', 'user_id'));
    }
    public function treatment($id) {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            $notification = ['message' => __('No appointment found.'), 'alert-type' => 'error'];
            return to_route('doctor.today.appointment')->with($notification);
        }
        if ($appointment->payment_status == 1) {
            if ($appointment->already_treated == 0) {
                if ($appointment?->doctor_id == doctorAuth()->id) {
                    $habits = Habit::active()->get();
                    $medicines = Medicine::active()
                        ->whereHas('translations', function ($query) {
                            $query->orderBy('name', 'asc');
                        })->get();

                    $medicineTypes = MedicineType::active()->get();
                    $dosages = $this->dosages;
                    return view('doctor.appointment.treatment', compact('appointment', 'habits', 'medicines', 'medicineTypes', 'dosages'));
                } else {
                    $notification = ['message' => __('Something went wrong'), 'alert-type' => 'error'];
                    return to_route('doctor.new.appointment')->with($notification);
                }

            } else {
                return to_route('doctor.already.treatment', $id);
            }
        } else {
            $notification = ['message' => __('Something went wrong'), 'alert-type' => 'error'];
            return back()->with($notification);
        }

    }
    public function storeTreatment(Request $request, $id) {
        $appointment = Appointment::find($id);
        if ($appointment->payment_status == 1) {
            if ($appointment?->already_treated == 0) {
                if ($appointment->doctor_id == doctorAuth()->id) {
                    $medicineAvailable = $request->medicine_name[0];
                    if ($medicineAvailable != null) {

                        UserDetails::where('user_id', $appointment?->user_id)->update(['weight' => $request?->weight]);

                        $habits = '';
                        if ($request?->habit) {
                            $habits = json_encode($request?->habit);
                        }
                        $appointment->blood_pressure = $request?->blood_pressure;
                        $appointment->pulse_rate = $request?->pulse_rate;
                        $appointment->temperature = $request?->temperature;
                        $appointment->problem_description = $request?->problem_description;
                        $appointment->habits = $habits;
                        $appointment->already_treated = 1;
                        $appointment->save();

                        // medicine insert
                        foreach ($request?->medicine_name as $index => $medicine) {
                            if ($medicine != null) {
                                Prescribe::create([
                                    'appointment_id' => $id,
                                    'medicine_type'  => $request?->medicine_type[$index],
                                    'medicine_name'  => $request?->medicine_name[$index],
                                    'dosage'         => $request?->dosage[$index],
                                    'number_of_day'  => $request?->day[$index],
                                    'time'           => $request?->time[$index],
                                ]);
                            }
                        }

                        // insert advice section
                        Advice::create([
                            'appointment_id'   => $id,
                            'test_description' => $request?->test_description,
                            'advice'           => $request?->advice,
                        ]);
                        $notification = ['message' => __('Create Successfully'), 'alert-type' => 'success'];
                        return to_route('doctor.already.treatment', $id)->with($notification);
                    } else {
                        $notification = ['message' => __('Something went wrong'), 'alert-type' => 'error'];
                        return back()->with($notification);
                    }
                } else {
                    $notification = ['message' => __('Something went wrong'), 'alert-type' => 'error'];
                    return to_route('doctor.today.appointment')->with($notification);
                }
            } else {
                return to_route('doctor.already.treatment', $id);
            }
        } else {
            $notification = ['message' => __('Something went wrong'), 'alert-type' => 'error'];
            return back()->with($notification);
        }

    }
    public function editTreatment($id) {
        $appointment = Appointment::find($id);
        $doctor_id = doctorAuth()->id;

        if ($appointment) {
            if ($appointment?->already_treated == 1 && $appointment?->doctor_id == $doctor_id) {
                $habits = Habit::active()->get();
                $medicines = Medicine::active()
                    ->whereHas('translations', function ($query) {
                        $query->orderBy('name', 'asc');
                    })->get();

                $medicineTypes = MedicineType::active()->get();
                $dosages = $this->dosages;
                return view('doctor.appointment.edit_treatment', compact('appointment', 'habits', 'medicines', 'medicineTypes', 'dosages'));
            } else if ($appointment->already_treated == 0 && $appointment->doctor_id == $doctor_id) {
                return to_route('doctor.treatment', $id);
            } else {
                $notification = ['message' => __('Something went wrong'), 'alert-type' => 'error'];
                return to_route('doctor.today.appointment')->with($notification);
            }
        } else {
            $notification = ['message' => __('Something went wrong'), 'alert-type' => 'error'];
            return to_route('doctor.all.appointment')->with($notification);
        }

    }
    public function updateTreatment(Request $request, $id) {
        $appointment = Appointment::find($id);
        if ($appointment?->already_treated == 1) {
            if ($appointment?->doctor_id == doctorAuth()->id) {
                $medicineAvailable = $request->medicine_name[0];
                if ($medicineAvailable != null) {
                    UserDetails::where('user_id', $appointment?->user_id)->update(['weight' => $request?->weight]);

                    $habits = '';
                    if ($request->habit) {
                        $habits = json_encode($request->habit);
                    }
                    $appointment->blood_pressure = $request?->blood_pressure;
                    $appointment->pulse_rate = $request?->pulse_rate;
                    $appointment->temperature = $request?->temperature;
                    $appointment->problem_description = $request?->problem_description;
                    $appointment->habits = $habits;
                    $appointment->save();

                    $oldMedicines = Prescribe::where('appointment_id', $id)->get();
                    // medicine insert
                    foreach ($request->medicine_name as $index => $medicine) {
                        if ($medicine != null) {
                            Prescribe::create([
                                'appointment_id' => $id,
                                'medicine_type'  => $request->medicine_type[$index],
                                'medicine_name'  => $request->medicine_name[$index],
                                'dosage'         => $request->dosage[$index],
                                'number_of_day'  => $request->day[$index],
                                'time'           => $request->time[$index],
                            ]);
                        }
                    }

                    // delete old medicine
                    foreach ($oldMedicines as $oldMedicine) {
                        Prescribe::destroy($oldMedicine->id);
                    }

                    // insert advice section
                    Advice::updateOrCreate(
                        ['appointment_id' => $id],
                        [
                            'test_description' => $request?->test_description,
                            'advice'           => $request?->advice,
                        ]
                    );

                    $notification = ['message' => __('Updated successfully'), 'alert-type' => 'success'];
                    return to_route('doctor.already.treatment', $id)->with($notification);
                } else {
                    $notification = ['message' => __('Something went wrong'), 'alert-type' => 'error'];
                    return back()->with($notification);
                }
            } else {
                $notification = ['message' => __('Something went wrong'), 'alert-type' => 'error'];
                return to_route('doctor.today.appointment')->with($notification);
            }
        } else {
            $notification = ['message' => __('Something went wrong'), 'alert-type' => 'error'];
            return back()->with($notification);
        }
    }
    public function deleteAppointmentPrescribe($id) {
        Prescribe::destroy($id);
        $notification = __('Deleted Successfully');
        return response()->json(['success' => true, 'message' => $notification]);
    }
    public function allReadyTreatment($id) {
        $appointment = Appointment::find($id);
        if ($appointment) {
            if ($appointment->already_treated == 1) {
                return view('doctor.appointment.already_treated', compact('appointment'));
            } else {
                return to_route('doctor.treatment', $id);
            }
        } else {
            $notification = ['message' => __('Something went wrong'), 'alert-type' => 'error'];
            return to_route('doctor.all.appointment')->with($notification);
        }

    }
    function printPrescription($id) {
        $appointment = doctorAuth()->appointments()->treated()->find($id);

        if ($appointment) {
            $data = [
                'appointment'         => $appointment,
            ];
            $pdf = Pdf::loadView( 'prescription', $data )->setPaper( 'a4', 'landscape' );
            return $pdf->stream('prescription_'.$appointment->id. '.pdf' );
        } else {
            $notification = __('No appointment found.');
            $notification = ['message' => $notification, 'alert-type' => 'error'];

            return redirect()->back()->with($notification);
        }
    }
    public function paymentHistory(Request $request) {
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays(30);

        $startDateFormatted = $startDate->format('Y-m-d H:i:s');
        $endDateFormatted = $endDate->format('Y-m-d H:i:s');

        $query = Appointment::query();
        $query->where('doctor_id', doctorAuth()?->id)->paymentSuccess();

        $payment = $query->whereBetween('created_at', [$startDateFormatted, $endDateFormatted])->sum('appointment_fee_usd');
        $total_appointment = $query->whereBetween('created_at', [$startDateFormatted, $endDateFormatted])->count();

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $from_date = date('Y-m-d', strtotime($request->from_date));
            $to_date = date('Y-m-d', strtotime($request->to_date));
            $query->whereBetween('date', array($from_date, $to_date));
        }

        $orderBy = $request->filled('order_by') && $request->order_by == 1 ? 'asc' : 'desc';

        if ($request->filled('par-page')) {
            $appointments = $request->get('par-page') == 'all' ? $query->orderBy('id', $orderBy)->get() : $query->orderBy('id', $orderBy)->paginate($request->get('par-page'))->withQueryString();
        } else {
            $appointments = $query->orderBy('id', $orderBy)->paginate(10)->withQueryString();
        }
        return view('doctor.payment.index', compact('appointments', 'payment', 'total_appointment'));

    }
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
