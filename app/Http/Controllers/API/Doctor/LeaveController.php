<?php

namespace App\Http\Controllers\API\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeaveController extends Controller {
    public function index(): JsonResponse {
        $doctor = auth()->guard('doctor_api')->user();
        $leaves = $doctor->leaves()->orderByDesc('date')->paginate(10);
        return response()->json(['status' => 'success', 'data' => $leaves], 200);
    }

    public function store(Request $request): JsonResponse {
        $validator = Validator::make($request->all(), ['date' => 'required|unique:leaves,date', 'reason' => 'required'], ['date.required' => __('Date is required'), 'date.unique' => __('You have already submitted a leave request for this day.'), 'reason.required' => __('Reason is required')]);
        if ($validator->fails()) {
            return response()->json(['status' => 'failed', 'message' => $validator->errors()], 422);
        }
        $doctor = auth()->guard('doctor_api')->user();
        $doctor->leaves()->create($request->only(['date', 'reason']));
        return response()->json(['status' => 'success', 'message' => __('Created Successfully')], 201);
    }

    public function update(Request $request, $id): JsonResponse {
        $doctor = auth()->guard('doctor_api')->user();
        $leave = $doctor->leaves()->where('id', $id)->first();
        if (!$leave) {
            return response()->json(['status' => 'error', 'message' => __('Not Found!')], 404);
        }

        $validator = Validator::make($request->all(), ['date' => 'required|unique:leaves,date,' . $id . ',id', 'reason' => 'required'], ['date.required' => __('Date is required'), 'date.unique' => __('You have already submitted a leave request for this day.'), 'reason.required' => __('Reason is required')]);
        if ($validator->fails()) {
            return response()->json(['status' => 'failed', 'message' => $validator->errors()], 422);
        }

        $leave->update($request->only(['date', 'reason']));
        return response()->json(['status' => 'success', 'message' => __('Updated successfully')], 200);
    }

    public function destroy($id) {
        $doctor = auth()->guard('doctor_api')->user();
        $leave = $doctor->leaves()->where('id', $id)->first();
        if ($leave) {
            $leave->delete();
            return response()->json(['status' => 'success', 'message' => __('Deleted Successfully')], 200);
        }
        return response()->json(['status' => 'error', 'message' => __('Not Found!')], 404);
    }
}
