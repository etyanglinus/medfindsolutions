<?php

namespace App\Http\Controllers\Doctor\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Modules\Doctor\app\Models\Doctor;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function custom_reset_password_page(Request $request, $token) {

        $doctor = Doctor::select('id', 'name', 'email', 'forget_password_token')->where('forget_password_token', $token)->first();

        if (!$doctor) {
            $notification = __('Invalid token, please try again');
            $notification = ['message' => $notification, 'alert-type' => 'error'];

            return redirect()->route('password.request')->with($notification);
        }

        return view('doctor.auth.reset-password', ['admin' => $doctor, 'token' => $token]);
    }

    /**
     * Handle an incoming new password request.
     */
    public function custom_reset_password_store(Request $request, $token) {

        $setting = Cache::get('setting');

        $rules = [
            'email'    => 'required',
            'password' => 'required|min:4|confirmed',
        ];
        $customMessages = [
            'email.required'    => __('Email is required'),
            'password.required' => __('Password is required'),
            'password.min'      => __('Password must be 4 characters'),
        ];
        $this->validate($request, $rules, $customMessages);

        $doctor = Doctor::select('id', 'name', 'email', 'forget_password_token')->where('forget_password_token', $token)->where('email', $request->email)->first();

        if (!$doctor) {
            $notification = __('Invalid token, please try again');
            $notification = ['message' => $notification, 'alert-type' => 'error'];

            return redirect()->back()->with($notification);
        }

        $doctor->password = Hash::make($request->password);
        $doctor->forget_password_token = null;
        $doctor->save();

        $notification = __('Password Reset successfully');
        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return redirect()->route('login',['type'=>'doctor'])->with($notification);

    }
}
