<?php

namespace App\Http\Controllers\API;

use App\Models\ShoppingCart;
use Illuminate\Http\Request;
use Modules\Day\app\Models\Day;
use Illuminate\Http\JsonResponse;
use Modules\Leave\app\Models\Leave;
use App\Http\Controllers\Controller;
use Modules\Doctor\app\Models\Doctor;
use Illuminate\Support\Facades\Validator;
use Modules\Doctor\app\Models\Department;
use Modules\Schedule\app\Models\Schedule;

class AppointmentController extends Controller {
    public function getDepartmentDoctor($department_id): JsonResponse {
        $doctors = Doctor::where(['department_id' => $department_id])->active()->verify()->paid()->get();
        if ($doctors) {
            return response()->json(['status' => 'success', 'data' => $doctors], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }
    public function getAppointmentSchedule(Request $request): JsonResponse {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required',
            'date'      => 'required',
        ], [
            'doctor_id.required' => __('Doctor is required'),
            'date.required'      => __('Date is required'),
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'failed', 'message' => $validator->errors()], 422);
        }
        $leave = Leave::where(['doctor_id' => $request->doctor_id, 'date' => $request->date])->count();

        if ($leave == 0) {
            $doctor_id = $request->doctor_id;
            $day_name = date('l', strtotime($request->date));
            $day = Day::where('slug', strtolower($day_name))->first();
            $schedules = Schedule::where(['doctor_id' => $doctor_id, 'day_id' => $day?->id])->get();

            if ($schedules->count() != 0) {
                return response()->json(['status' => 'success', 'data' => $schedules], 200);
            } else {
                return response()->json(['status' => 'error', 'message' => __('Schedule Not Found')], 404);
            }
        } else {
            return response()->json(['status' => 'error', 'message' => __('Doctor is unavailable on the selected date')], 404);
        }
    }
    public function createAppointment(Request $request): JsonResponse {
        $currency_code = strtoupper(request()->query('currency', 'KES'));
        $validator = Validator::make($request->all(), [
            'doctor_id'     => 'required',
            'department_id' => 'required',
            'date'          => 'required',
            'schedule_id'   => 'required',
        ], [
            'doctor_id.required'     => __('Doctor is required'),
            'department_id.required' => __('The department is required.'),
            'date.required'          => __('Date is required'),
            'schedule_id.required'   => __('Schedule is required'),
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'failed', 'message' => $validator->errors()], 422);
        }
        $user = auth()->guard('api')->user();
        $doctor_id = $request->doctor_id;
        $department_id = $request->department_id;
        $date = $request->date;
        $schedule_id = $request->schedule_id;

        $schedule = Schedule::find($schedule_id);
        $doctor = Doctor::where(['id' => $doctor_id, 'department_id' => $department_id])->first();
        $department = Department::find($department_id);

        // Check if doctor, department, or schedule is found
        if (!$doctor) {
            return response()->json(['status' => 'error', 'message' => __('Doctor not found')], 404);
        } elseif (!$department) {
            return response()->json(['status' => 'error', 'message' => __('Department not found')], 404);
        } elseif (!$schedule) {
            return response()->json(['status' => 'error', 'message' => __('Schedule not found')], 404);
        }

        $exist_item = ShoppingCart::where(['user_id' => $user->id, 'doctor_id' => $doctor_id, 'schedule_id' => $schedule_id, 'date' => $date])->count();

        if($exist_item > 0) {
            return response()->json(['status' => 'error', 'message' => __('Item is already in the cart.')], 403);
        }

        $item = new ShoppingCart();
        $item->user_id = $user->id;
        $item->name = $doctor->name;
        $item->doctor_id = $doctor_id;
        $item->department_id = $department->id;
        $item->qty = 1;
        $item->price = $doctor->fee;
        $item->location_id = $doctor->location->id ?? null;
        $item->date = $date;
        $item->time = $schedule->start_time . '-' . $schedule->end_time;
        $item->schedule_id = $schedule->id;
        $item->day_id = $schedule->day_id;
        $item->save();

        // Wrap the price with the currency conversion function
        $item->price = apiCurrency($currency_code, $item->price ?? 0);

        // Return success response
        return response()->json(['status' => 'success', 'message' => __('Appointment Created Successfully'), 'data' => $item], 201);
    }
    public function totalAppointment(): JsonResponse {
        $user = auth()->guard('api')->user();
        $currency_code = strtoupper(request()->query('currency', 'KES'));

        $totalAppointment =  ShoppingCart::where('user_id', $user->id)->get();
        $sub_total = $totalAppointment->sum('price');
        $count = $totalAppointment->sum('qty');

        // Wrap the price with apiCurrency for each item in totalAppointment
        $totalAppointment->transform(function ($item) use ($currency_code) {
            $item->price = apiCurrency($currency_code, $item->price ?? 0);
            return $item;
        });
        if ($totalAppointment) {
            return response()->json(['status' => 'success', 'data' => [
                'sub_total' => apiCurrency($currency_code, $sub_total ?? 0),
                'total_qty' => $count,
                'items' => $totalAppointment,
            ]], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }
    public function removeAppointment($id): JsonResponse {
        $user = auth()->guard('api')->user();
        $item = ShoppingCart::where(['user_id'=> $user->id,'id'=>$id])->first();
        if ($item) {
            $item->delete();
            return response()->json(['status' => 'success', 'message' => __('Deleted Successfully')], 200);
        }
        return response()->json(['status' => 'error', 'message' => __('Not Found!')], 404);
    }

}
