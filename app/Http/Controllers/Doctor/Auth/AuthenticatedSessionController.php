<?php

namespace App\Http\Controllers\Doctor\Auth;

use App\Http\Controllers\Controller;
use App\Rules\CustomRecaptcha;
use App\Traits\GlobalMailTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Doctor\app\Models\Doctor;
use Modules\Doctor\app\Services\SubscriptionAccessService;

class AuthenticatedSessionController extends Controller {
    use GlobalMailTrait;
    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request) {
        $setting = Cache::get('setting');

        $rules = [
            'email'                => 'required|email',
            'password'             => 'required',
            'g-recaptcha-response' => $setting->recaptcha_status == 'active' ? ['required', new CustomRecaptcha()] : '',
        ];

        $customMessages = [
            'email.required'                => __('Email is required'),
            'password.required'             => __('Password is required'),
            'g-recaptcha-response.required' => __('Please complete the recaptcha to submit the form'),
        ];
        $this->validate($request, $rules, $customMessages);

        $doctor = Doctor::where('email', $request->email)->first();

        if (! $doctor) {
            return redirect()->route('login', ['type' => 'doctor'])
                ->with(['message' => __('Invalid Credentials'), 'alert-type' => 'error']);
        }

        if (! \App\Support\PasswordInput::verify($doctor, $request->password)) {
            return redirect()->route('login', ['type' => 'doctor'])
                ->with(['message' => __('Invalid Credentials'), 'alert-type' => 'error']);
        }
        if (! $doctor->email_verified_at) {
            $verification = app(\App\Services\ProviderEmailVerification::class);
            return redirect()->route('login', ['type' => 'doctor'])
                ->with(['message' => $verification->message($doctor->email, $verification->sendLink('doctor', $doctor)), 'alert-type' => 'warning']);
        }

        $subscription = app(SubscriptionAccessService::class)->activeDoctorSubscription($doctor);

        // Log in regardless of approval status — the dashboard shows a
        // pending-approval banner to doctors who are not yet active.
        if (!$subscription && !$doctor->subscriptions()->where('status', 'approved')->exists()) {
            $message = $doctor->latestSubscription?->status === 'pending'
                ? __('Your payment is under review. Please wait for admin approval.')
                : __('Your account is inactive. Please complete registration and payment.');

            return redirect()->route('login', ['type' => 'doctor'])
                ->with(['message' => $message, 'alert-type' => 'error']);
        }

        Auth::guard('doctor')->login($doctor, $request->boolean('doctor_remember'));
        $request->session()->regenerate();
        if (!$subscription) {
            if (!$doctor->registration_token) $doctor->update(['registration_token' => Str::random(80)]);
            return redirect()->route('doctor.subscription.complete', ['token' => $doctor->registration_token])->with(['message' => __('Renew your subscription securely with Paystack.'), 'alert-type' => 'warning']);
        }

        $notification = ['message' => __('Logged in successfully.'), 'alert-type' => 'success'];
        $intendedUrl  = session()->get('url.intended');
        if ($intendedUrl && Str::contains($intendedUrl, '/doctor')) {
            return redirect()->intended(route('doctor.dashboard'))->with($notification);
        }
        return redirect()->route('doctor.dashboard')->with($notification);
    }

    public function resendVerification(): RedirectResponse {
        $doctor = Auth::guard('doctor')->user();

        if ($doctor->email_verified_at) {
            return redirect()->back()
                ->with(['message' => __('Your email is already verified.'), 'alert-type' => 'info']);
        }

        $doctor->email_verified_token = Str::random(100);
        $doctor->save();

        try {
            [$subject, $message] = $this->fetchEmailTemplate('user_verification', ['user_name' => $doctor->name]);
            $link = [__('CONFIRM YOUR EMAIL') => route('doctor.verification', $doctor->email_verified_token)];
            $this->sendMail($doctor->email, $subject, $message, $link, true);
            $notification = ['message' => __('Verification email sent! Please check your inbox.'), 'alert-type' => 'success'];
        } catch (\Exception $e) {
            info('Doctor resend verification failed: ' . $e->getMessage());
            $notification = ['message' => __('Could not send the email right now. Please try again later.'), 'alert-type' => 'error'];
        }

        return redirect()->back()->with($notification);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(): RedirectResponse {
        Auth::guard('doctor')->logout();

        $notification = __('Logged out successfully.');
        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return redirect()->route('login',['type'=>'doctor'])->with($notification);
    }
}
