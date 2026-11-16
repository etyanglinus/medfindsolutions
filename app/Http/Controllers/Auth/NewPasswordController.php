<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Rules\CustomRecaptcha;
use Illuminate\Validation\Rules;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;

class NewPasswordController extends Controller
{
    public function custom_reset_password_page(Request $request, $token)
    {

        $user = User::select('id', 'name', 'email', 'forget_password_token')->where('forget_password_token', $token)->first();

        if (! $user) {
            $notification = __('Invalid token, please try again');
            $notification = ['message' => $notification, 'alert-type' => 'error'];

            return redirect()->route('password.request')->with($notification);
        }

        return view('patient.profile.auth.reset-password', ['user' => $user, 'token' => $token]);
    }

    public function custom_reset_password_store(Request $request, $token)
    {

        $setting = Cache::get('setting');

        $rules = [
            'email' => 'required',
            'password' => 'required|min:4|confirmed',
            'g-recaptcha-response' => $setting->recaptcha_status == 'active' ? ['required', new CustomRecaptcha()] : '',
        ];
        $customMessages = [
            'email.required' => __('Email is required'),
            'password.required' => __('Password is required'),
            'password.min' => __('Password must be 4 characters'),
            'g-recaptcha-response.required' => __('Please complete the recaptcha to submit the form'),
        ];
        $this->validate($request, $rules, $customMessages);

        $user = User::select('id', 'name', 'email', 'forget_password_token')->where('forget_password_token', $token)->where('email', $request->email)->first();

        if (! $user) {
            $notification = __('Invalid token, please try again');
            $notification = ['message' => $notification, 'alert-type' => 'error'];

            return redirect()->back()->with($notification);
        }

        $user->password = Hash::make($request->password);
        $user->forget_password_token = null;
        $user->save();

        $notification = __('Password Reset successfully');
        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return redirect()->route('login')->with($notification);

    }
}
