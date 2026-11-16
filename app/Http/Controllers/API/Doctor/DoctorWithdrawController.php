<?php

namespace App\Http\Controllers\API\Doctor;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Appointment\app\Models\Appointment;
use Modules\PaymentWithdraw\app\Models\WithdrawMethod;
use Modules\PaymentWithdraw\app\Models\WithdrawRequest;

class DoctorWithdrawController extends Controller {
    public function getWithDrawMethodInfo($id): JsonResponse {
        $method = WithdrawMethod::find($id);
        $currency_code = 'KES';

        if ($method) {
            return response()->json([
                'status' => 'success',
                'data'   => [
                    'min_amount' => specific_currency_with_icon($currency_code, $method?->min_amount),
                    'max_amount' => specific_currency_with_icon($currency_code, $method?->max_amount),
                    'withdraw_charge' => $method?->withdraw_charge.'%',
                    'description' => $method?->description,
                ],
            ], 200);
        }

        return response()->json(['status' => 'error', 'message' => 'Withdraw method not found!'], 404);
    }
    public function withdrawMethods(): JsonResponse {
        $methods = WithdrawMethod::active()->get();
        if ($methods) {
            return response()->json(['status' => 'success', 'data' => $methods], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }
    public function withdrawSummary(): JsonResponse {
        $doctor_id = auth()->guard('doctor_api')->user()?->id;
        $currency_code = 'KES';

        $appointment = Appointment::where('doctor_id', $doctor_id)->paymentSuccess()->treated()->get();
        $withdraw = WithdrawRequest::where('doctor_id', $doctor_id)->active()->get();

        $total_earning = $appointment->sum('appointment_fee_usd');
        $total_withdraw = $withdraw->sum('total_amount');
        $current_balance = $total_earning - $total_withdraw;

        return response()->json([
            'status' => 'success',
            'data'   => [
                'current_balance' => specific_currency_with_icon($currency_code, $current_balance ?? 0),
                'total_earning' => specific_currency_with_icon($currency_code, $total_earning ?? 0),
                'total_withdraw' => specific_currency_with_icon($currency_code, $total_withdraw ?? 0),
            ],
        ], 200);
    }

    public function withdrawList(): JsonResponse {
        $doctor_id = auth()->guard('doctor_api')->user()?->id;
        $withdraws = WithdrawRequest::where('doctor_id', $doctor_id)->latest()->paginate(15);

        return response()->json([
            'status' => 'success',
            'data'   =>  $withdraws,
        ], 200);
    }


    public function store(Request $request): JsonResponse {
        $validator = Validator::make($request->all(), [
            'withdraw_method_id' => 'required',
            'amount'             => 'required|numeric',
            'account_info'       => 'required',
        ], [
            'withdraw_method_id.required' => __('Payment Method filed is required'),
            'amount.required'             => __('Withdraw amount filed is required'),
            'amount.numeric'              => __('Please provide valid numeric number'),
            'account_info.required'       => __('Account filed is required'),
            
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'failed', 'message' => $validator->errors()], 422);
        }

        $doctor = auth()->guard('doctor_api')->user();

        if (!$doctor?->fee) {
            return response()->json([
                'status' => 'error',
                'message' => __('Please fill up your profile information before payment withdraw')
            ], 400);
        }

        $appointment = Appointment::where('doctor_id', $doctor->id)->paymentSuccess()->treated()->get();
        $withdraw = WithdrawRequest::where('doctor_id', $doctor->id)->get();

        $total_earning = $appointment->sum('appointment_fee_usd');
        $total_withdraw = $withdraw->sum('total_amount');
        $current_balance = $total_earning - $total_withdraw;

        if ($request->amount > $current_balance) {
            return response()->json([
                'status' => 'error',
                'message' => __('Sorry! Your Payment request is more than your current balance')
            ], 400);
        }

        $method = WithdrawMethod::whereId($request?->withdraw_method_id)->first();
        if ($request->amount >= $method->min_amount && $request->amount <= $method->max_amount) {
            $withdraw = new WithdrawRequest();
            $withdraw->doctor_id = $doctor?->id;
            $withdraw->withdraw_method_id = $request?->withdraw_method_id;
            $withdraw->total_amount = $request?->amount;
            $withdraw_request = $request?->amount;
            $withdraw_amount = ($method?->withdraw_charge / 100) * $withdraw_request;
            $withdraw->withdraw_amount = $request?->amount - $withdraw_amount;
            $withdraw->withdraw_charge = $method?->withdraw_charge;
            $withdraw->account_info = $request?->account_info;
            $withdraw->save();

            return response()->json([
                'status' => 'success',
                'message' => __('Withdraw request sent successfully, please wait for admin approval')
            ], 201);

        } else {
            return response()->json([
                'status' => 'error',
                'message' => __('Your amount range is not available')
            ], 400);
        }
    }

    public function showWithdraw($id): JsonResponse {
        $withdraw = WithdrawRequest::with('withdraw_method')->find($id);
        if ($withdraw) {
            return response()->json([
                'status' => 'success',
                'data'   => $withdraw
            ], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Withdraw method not found!'], 404);

    }
}
