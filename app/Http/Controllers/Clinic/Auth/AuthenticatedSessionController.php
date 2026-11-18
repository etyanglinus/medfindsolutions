<?php

namespace App\Http\Controllers\Clinic\Auth;

use App\Http\Controllers\Controller;
use App\Rules\CustomRecaptcha;
use App\Traits\GlobalMailTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Doctor\app\Models\Clinic;
use Modules\Doctor\app\Services\SubscriptionAccessService;

class AuthenticatedSessionController extends Controller {
    use GlobalMailTrait;

    public function store(Request $request): RedirectResponse {
        $setting = Cache::get('setting');

        $rules = [
            'email'                => 'required|email',
            'password'             => 'required',
            'g-recaptcha-response' => $setting->recaptcha_status == 'active'
                ? ['required', new CustomRecaptcha()] : '',
        ];

        $this->validate($request, $rules, [
            'email.required'                => __('Email is required'),
            'password.required'             => __('Password is required'),
            'g-recaptcha-response.required' => __('Please complete the recaptcha to submit the form'),
        ]);

        $clinic = Clinic::where('email', $request->email)->first();

        if (! $clinic) {
            return redirect()->route('login', ['type' => 'clinic'])
                ->with(['message' => __('Invalid credentials.'), 'alert-type' => 'error']);
        }

        if (! $clinic->status) {
            $message = $clinic->latestSubscription?->status === 'pending'
                ? __('Your payment is under review. Please wait for admin approval.')
                : __('Your account is inactive. Please complete registration and payment.');

            return redirect()->route('login', ['type' => 'clinic'])
                ->with(['message' => $message, 'alert-type' => 'error']);
        }

        $activeSubscription = app(SubscriptionAccessService::class)->activeClinicSubscription($clinic);
        if (!$activeSubscription && !$clinic->subscriptions()->where('status', 'approved')->exists()) {
            return redirect()->route('login', ['type' => 'clinic'])
                ->with(['message' => __('Your subscription has expired. Please renew to access your account.'), 'alert-type' => 'error']);
        }

        if (! \App\Support\PasswordInput::verify($clinic, $request->password)) {
            return redirect()->route('login', ['type' => 'clinic'])
                ->with(['message' => __('Invalid credentials.'), 'alert-type' => 'error']);
        }
        if (! $clinic->email_verified_at) {
            $verification = app(\App\Services\ProviderEmailVerification::class);
            return redirect()->route('login', ['type' => 'clinic'])
                ->with(['message' => $verification->message($clinic->email, $verification->sendLink('clinic', $clinic)), 'alert-type' => 'warning']);
        }

        Auth::guard('clinic')->login($clinic, $request->boolean('remember'));
        $request->session()->regenerate();
        if (!$activeSubscription) return redirect()->route('clinic.subscription.status')->with(['message' => __('Renew your subscription securely with Paystack.'), 'alert-type' => 'warning']);

        return redirect()->route('clinic.dashboard')
            ->with(['message' => __('Logged in successfully.'), 'alert-type' => 'success']);
    }

    public function resendVerification(): RedirectResponse {
        $clinic = Auth::guard('clinic')->user();

        if ($clinic->email_verified_at) {
            return redirect()->back()
                ->with(['message' => __('Your email is already verified.'), 'alert-type' => 'info']);
        }

        $clinic->email_verified_token = Str::random(100);
        $clinic->save();

        try {
            $verifyUrl = route('clinic.verification', $clinic->email_verified_token);
            $subject   = __('Verify your MedFind Clinic email');
            $body      = __('Hello') . ' ' . $clinic->name . ', ' .
                         __('please click the link below to verify your email address:') .
                         ' <a href="' . $verifyUrl . '">' . $verifyUrl . '</a>';
            $this->sendMail($clinic->email, $subject, $body, [__('Verify Email') => $verifyUrl]);
            $notification = ['message' => __('Verification email sent! Please check your inbox.'), 'alert-type' => 'success'];
        } catch (\Exception $e) {
            info('Clinic resend verification failed: ' . $e->getMessage());
            $notification = ['message' => __('Could not send the email right now. Please try again later.'), 'alert-type' => 'error'];
        }

        return redirect()->back()->with($notification);
    }

    public function destroy(): RedirectResponse {
        Auth::guard('clinic')->logout();

        return redirect()->route('login', ['type' => 'clinic'])
            ->with(['message' => __('Logged out successfully.'), 'alert-type' => 'success']);
    }
}
