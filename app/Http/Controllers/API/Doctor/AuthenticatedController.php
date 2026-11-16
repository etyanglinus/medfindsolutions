<?php

namespace App\Http\Controllers\API\Doctor;

use App\Http\Controllers\Controller;
use App\Traits\GlobalMailTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Doctor\app\Enums\DoctorStatus;
use Modules\Doctor\app\Models\Doctor;
use Modules\Language\app\Enums\TranslationModels;
use Modules\Language\app\Traits\GenerateTranslationTrait;

class AuthenticatedController extends Controller {
    use GenerateTranslationTrait, GlobalMailTrait;

    public function register(Request $request): JsonResponse {
        if (!cache()->get('setting')?->doctor_can_register) {
            return response()->json([
                'message' => __('Doctor registration is currently disabled'),
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            'name'          => ['required', 'string', 'max:255'],
            'designations'  => 'required',
            'email'         => ['required', 'string', 'email', 'max:255', 'unique:' . Doctor::class],
            'phone'         => 'required',
            'password'      => ['required', 'confirmed', 'min:4', 'max:100'],
            'department_id' => 'sometimes|exists:departments,id',
            'location_id'   => 'sometimes|exists:locations,id',
        ], [
            'name.required'          => __('Name is required'),
            'designations.required'  => __('Designations is required.'),
            'email.required'         => __('Email is required'),
            'email.unique'           => __('Email already exist'),
            'phone.required'         => __('Phone number is required.'),
            'password.required'      => __('Password is required'),
            'password.confirmed'     => __('Confirm password does not match'),
            'password.min'           => __('You have to provide minimum 4 character password'),
            'department_id.required' => __('The department is required.'),
            'department_id.exists'   => __('The selected department is invalid.'),
            'location_id.required'   => __('The location is required.'),
            'location_id.exists'     => __('The selected location is invalid.'),
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'failed', 'message' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $slug = Str::slug($request->name);
            if (Doctor::whereSlug($slug)->exists()) {
                $slug = $slug . '-' . Str::random(5);
            }

            $doctor = Doctor::create([
                'name'                 => $request->name,
                'slug'                 => $slug,
                'designations'         => $request->designations,
                'phone'                => $request->phone,
                'email'                => $request->email,
                'fee'                  => 0,
                'department_id'        => $request->department_id,
                'location_id'          => $request->location_id,
                'password'             => Hash::make($request->password),
                'email_verified_token' => Str::random(100),
            ]);
            

            [$subject, $message] = $this->fetchEmailTemplate('user_verification', ['user_name' => $doctor->name]);
            $link = [__('CONFIRM YOUR EMAIL') => route('doctor.verification', $doctor->email_verified_token)];
            $this->sendMail($doctor->email, $subject, $message, $link);

            $this->generateTranslations(
                TranslationModels::Doctor,
                $doctor,
                'doctor_id',
                $request,
            );
            DB::commit();

            // Return success message
            return response()->json([
                'message' => __('A verification link has been sent to your mail, please verify and enjoy our service'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => __('Registration failed due to an issue with sending the verification email. Please try again later.'),
            ], 500);

        }
    }

    public function login(Request $request): JsonResponse {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required',
        ], [
            'email.required'    => __('Email is required'),
            'password.required' => __('Password is required'),
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'failed', 'message' => $validator->errors()], 422);
        }

        $doctor = Doctor::where('email', $request->email)->first();
        if ($doctor) {
            if ($doctor->status == DoctorStatus::ACTIVE->value) {
                if ($doctor->email_verified_at == null) {
                    return response()->json(['status' => 'error', 'message' => __('Please verify your email')], 403);
                }
                if (Hash::check($request->password, $doctor->password)) {
                    $token = $doctor->createToken('doctorToken', ['*'], now()->addMonth())->plainTextToken;
                    return response()->json(['status' => 'success', 'message' => __('Logged in successfully.'), 'token' => $token, 'doctor_id'=>$doctor->id], 200);
                } else {
                    return response()->json(['status' => 'error', 'message' => __('Invalid Credentials')], 401);
                }
            } else {
                return response()->json(['status' => 'error', 'message' => __('Inactive account')], 403);
            }
        } else {
            return response()->json(['status' => 'error', 'message' => __('Invalid Credentials')], 404);
        }
    }
    public function forgetPassword(Request $request): JsonResponse {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ], [
            'email.required' => __('Email is required'),
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'failed', 'message' => $validator->errors()->first()], 422);
        }

        $doctor = Doctor::where('email', $request->email)->first();

        if ($doctor) {
            $doctor->forget_password_token = Str::random(100);
            $doctor->save();

            $token = $doctor->forget_password_token;

            [$subject, $message] = $this->fetchEmailTemplate('password_reset', ['user_name' => $doctor->name,'token' => $token]);
            $link = [__('CONFIRM YOUR EMAIL') => route('doctor.password.reset', $token)];

            try {
                $this->sendMail($doctor->email, $subject, $message, $link);
                return response()->json(['status' => 'success', 'message' => __('A password reset link has been sent to your email.')], 200);
            } catch (\Exception $e) {
                return response()->json(['status' => 'error', 'message' => __('Failed to send password reset email, please try again later.')], 500);
            }
        } else {
            return response()->json(['status' => 'error', 'message' => __('Email does not exist.')], 404);
        }
    }
    public function resetPassword(Request $request): JsonResponse {
        $validator = Validator::make($request->all(), [
            'forget_password_token' => ['required', 'string'],
            'email'                 => ['required', 'string', 'email'],
            'password'              => ['required', 'confirmed', 'min:4', 'max:100'],
        ], [
            'forget_password_token.required' => __('Forget password token is required'),
            'email.required'                 => __('Email is required'),
            'password.required'              => __('Password is required'),
            'password.confirmed'             => __('Confirm password does not match'),
            'password.min'                   => __('You have to provide minimum 4 character password'),
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'failed', 'message' => $validator->errors()], 422);
        }

        $doctor = Doctor::select('id', 'name', 'email', 'forget_password_token')->where('forget_password_token', $request->forget_password_token)->where('email', $request->email)->first();

        if (!$doctor) {
            return response()->json([
                'status'  => 'error',
                'message' => __('Invalid token, please try again'),
            ], 400);
        }

        // Update the user's password
        $doctor->password = Hash::make($request->password);
        $doctor->forget_password_token = null;
        $doctor->save();

        return response()->json([
            'status'  => 'success',
            'message' => __('Password reset successfully'),
        ], 200);
    }

    public function logout(): JsonResponse {
        auth()->guard('doctor_api')->user()->currentAccessToken()->delete();
        return response()->json(['status' => 'success', 'message' => __('Logged out successfully.')]);

    }
    public function logoutAllApp(): JsonResponse {
        auth()->guard('doctor_api')->user()->tokens()->delete();
        return response()->json(['status' => 'success', 'message' => __('Logged out successfully.')]);
    }
}
