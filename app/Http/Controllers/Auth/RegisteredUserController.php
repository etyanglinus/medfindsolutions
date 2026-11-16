<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Rules\CustomRecaptcha;
use App\Traits\GlobalMailTrait;
use Illuminate\Support\Facades\DB;
use App\Services\MailSenderService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Modules\Doctor\app\Models\Location;
use Modules\Doctor\app\Models\LicensingBody;
use Modules\Doctor\app\Models\InsuranceProvider;
use App\Traits\GetGlobalInformationTrait;
use Modules\Doctor\app\Models\Department;

class RegisteredUserController extends Controller {
    use GetGlobalInformationTrait, GlobalMailTrait;

    protected $setting;

    public function __construct() {
        $this->setting = cache()->get('setting');
    }

    public function create(): View {
        abort(404);
    }

    public function providerCreate(): View {
        if (! $this->setting?->doctor_can_register) abort(404);
        $departments = Department::with('translation')->active()->get();
        $locations = Location::with('translation')->active()->get();
        $licensingBodies = LicensingBody::activeDoctorBodies();
        $facilityLicensingBodies = LicensingBody::activeFacilityBodies();
        $insuranceProviders = InsuranceProvider::active();
        return view('patient.profile.auth.provider-register', compact('departments', 'locations', 'licensingBodies', 'facilityLicensingBodies', 'insuranceProviders'));
    }

    public function store(Request $request): RedirectResponse {
        abort(404);
        $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'email'                => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
            'password'             => ['required', 'confirmed', 'min:4', 'max:100'],
            'g-recaptcha-response' => $this->setting?->recaptcha_status == 'active' ? ['required', new CustomRecaptcha()] : '',
        ], [
            'name.required'                 => __('Name is required'),
            'email.required'                => __('Email is required'),
            'email.unique'                  => __('Email already exist'),
            'password.required'             => __('Password is required'),
            'password.confirmed'            => __('Confirm password does not match'),
            'password.min'                  => __('You have to provide minimum 4 character password'),
            'g-recaptcha-response.required' => __('Please complete the recaptcha to submit the form'),
        ]);
        try {
            DB::beginTransaction();
            $user = User::create([
                'patient_id'         => date('ymdis'),
                'name'               => $request->name,
                'email'              => $request->email,
                'status'             => 'active',
                'is_banned'          => 'no',
                'password'           => Hash::make($request->password),
                'verification_token' => Str::random(100),
            ]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $notification = ['message' => __('Registration failed. Please try again.'), 'alert-type' => 'error'];
            return redirect()->back()->withInput()->with($notification);
        }

        // Account is saved — attempt mail separately so a mail failure
        // never rolls back the registration.
        try {
            (new MailSenderService)->sendVerifyMailSingleUser($user);
            $notification = ['message' => __('Account created! A verification link has been sent to your email — please verify to activate your account.'), 'alert-type' => 'success'];
        } catch (\Exception $e) {
            info('Verification mail failed for user ' . $user->id . ': ' . $e->getMessage());
            // Account was saved. Tell the user clearly without losing them.
            $notification = ['message' => __('Account created successfully! We could not send the verification email right now — please contact support or try logging in and requesting a new verification link.'), 'alert-type' => 'warning'];
        }

        return redirect()->route('login')->with($notification);

    }

    public function resendVerification(): RedirectResponse {
        $user = userAuth();

        if ($user->email_verified_at) {
            return redirect()->route('dashboard')
                ->with(['message' => __('Your email is already verified.'), 'alert-type' => 'info']);
        }

        // Rotate the token so old links expire
        $user->verification_token = Str::random(100);
        $user->save();

        try {
            (new MailSenderService)->sendVerifyMailSingleUser($user);
            $notification = ['message' => __('Verification email sent! Please check your inbox.'), 'alert-type' => 'success'];
        } catch (\Exception $e) {
            info('Resend verification failed for user ' . $user->id . ': ' . $e->getMessage());
            $notification = ['message' => __('Could not send the email right now. Please try again in a few minutes.'), 'alert-type' => 'error'];
        }

        return redirect()->back()->with($notification);
    }

    public function custom_user_verification($token) {
        $user = User::where('verification_token', $token)->first();
        if ($user) {

            if ($user->email_verified_at != null) {
                $notification = __('Email already verified');
                $notification = ['message' => $notification, 'alert-type' => 'error'];

                return redirect()->route('login')->with($notification);
            }

            $user->email_verified_at = date('Y-m-d H:i:s');
            $user->verification_token = null;
            $user->save();

            $notification = __('Email verified successfully');
            $notification = ['message' => $notification, 'alert-type' => 'success'];

            return redirect()->route('login')->with($notification);
        } else {
            $notification = __('Invalid token');
            $notification = ['message' => $notification, 'alert-type' => 'error'];

            return redirect()->route('register')->with($notification);
        }
    }
}
