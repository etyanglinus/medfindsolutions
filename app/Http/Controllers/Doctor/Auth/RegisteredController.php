<?php

namespace App\Http\Controllers\Doctor\Auth;

use Illuminate\View\View;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Rules\CustomRecaptcha;
use App\Traits\GlobalMailTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\SubscriptionCouponService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Modules\Doctor\app\Models\Doctor;
use Modules\Doctor\app\Models\DoctorSubscription;
use Modules\Doctor\app\Models\SubscriptionPaymentSetting;
use Modules\Doctor\app\Models\Location;
use App\Traits\GetGlobalInformationTrait;
use Modules\Doctor\app\Models\Department;
use Modules\Doctor\app\Enums\DoctorStatus;
use Modules\Doctor\app\Models\InsuranceProvider;
use Modules\Doctor\app\Models\LicensingBody;
use Modules\Doctor\app\Services\SubscriptionPlanService;
use Modules\Language\app\Enums\TranslationModels;
use Modules\Language\app\Traits\GenerateTranslationTrait;

class RegisteredController extends Controller {
    use GenerateTranslationTrait, GetGlobalInformationTrait, GlobalMailTrait;

    public function store(Request $request): RedirectResponse {
        $setting = Cache::get('setting');
        if(! $setting?->doctor_can_register) return redirect()->back();

        // Frees the email if an earlier sign-up with it was never paid/activated.
        app(\App\Services\UnfinishedSignupService::class)->clearForRegistration('doctor', $request->input('email'));
        $request->validate([
            'name'                     => ['required', 'string', 'max:255'],
            'licensing_body_id'        => ['required', 'integer', 'exists:licensing_bodies,id'],
            'insurance_provider_ids'   => ['nullable', 'array'],
            'insurance_provider_ids.*' => ['integer', 'exists:insurance_providers,id'],
            'email'                    => ['required', 'string', 'email', 'max:255', 'unique:' . Doctor::class],
            'phone'                    => 'required',
            'password'                 => ['required', 'confirmed', 'min:4', 'max:100'],
            'department_id'            => 'sometimes|exists:departments,id',
            'location_id'              => 'sometimes|exists:locations,id',
            'image'                    => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'g-recaptcha-response'     => $setting->recaptcha_status == 'active' ? ['required', new CustomRecaptcha()] : '',
        ], [
            'name.required'                        => __('Name is required'),
            'licensing_body_id.required'           => __('Licensing body is required.'),
            'licensing_body_id.exists'             => __('The selected licensing body is invalid.'),
            'insurance_provider_ids.array'         => __('The selected insurance providers are invalid.'),
            'insurance_provider_ids.*.exists'      => __('The selected insurance provider is invalid.'),
            'email.required'                       => __('Email is required'),
            'email.unique'                         => __('An account with this email already exists. Please log in, or use "Forgot password" if you cannot remember your password.'),
            'phone.required'                       => __('Phone number is required.'),
            'password.required'                    => __('Password is required'),
            'password.confirmed'                   => __('Confirm password does not match'),
            'password.min'                         => __('You have to provide minimum 4 character password'),
            'department_id.exists'                 => __('The selected department is invalid.'),
            'location_id.exists'                   => __('The selected location is invalid.'),
            'image.required'                       => __('Please upload a profile photo.'),
            'image.image'                          => __('The profile photo must be an image (JPG, PNG or WebP).'),
            'image.mimes'                          => __('The profile photo must be a JPG, PNG or WebP image.'),
            'image.max'                            => __('The profile photo must be 2 MB or smaller.'),
            'g-recaptcha-response.required'        => __('Please complete the recaptcha to submit the form'),
        ]);

        $locationId = Location::whereHas('translations', fn ($query) => $query->where('name', 'Nairobi'))->value('id');

        if (! $locationId) {
            return redirect()->back()->withInput()->with([
                'message' => __('The Nairobi location must be configured before doctors can register. Please contact support.'),
                'alert-type' => 'error',
            ]);
        }

        try {
            DB::beginTransaction();

            $slug = Str::slug($request->name);
            if(Doctor::whereSlug($slug)->exists()){
                $slug = $slug.'-'. Str::random(5);
            }

            $licensingBodyName = LicensingBody::find($request->licensing_body_id)?->name;
            $insuranceNames = InsuranceProvider::whereIn('id', $request->input('insurance_provider_ids', []))
                ->orderBy('name')
                ->pluck('name')
                ->toArray();

            $doctor = Doctor::create([
                'name'                   => $request->name,
                'slug'                   => $slug,
                'phone'                  => $request->phone,
                'email'                  => $request->email,
                'fee'                    => 0,
                'department_id'          => $request->department_id,
                'location_id'            => $locationId,
                'password'               => Hash::make($request->password),
                'status'                 => DoctorStatus::INACTIVE->value,
                'email_verified_token'   => Str::random(100),
                'registration_token'     => Str::random(100),
            ]);

            // Required at sign-up: every listing has a photo from day one.
            $doctor->forceFill(['image' => uploadAndOptimizeImage(file: $request->file('image'))])->save();

            $request->merge([
                'licensing_body'     => $licensingBodyName,
                'accepted_insurance' => $insuranceNames ? implode(', ', $insuranceNames) : null,
            ]);

            $this->generateTranslations(
                TranslationModels::Doctor,
                $doctor,
                'doctor_id',
                $request,
            );
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            info($e->getMessage());
            $notification = ['message' => __('Registration failed. Please try again.'), 'alert-type' => 'error'];
            return redirect()->back()->withInput()->with($notification);
        }

        // Account saved — attempt verification mail separately.
        try {
            [$subject, $message] = $this->fetchEmailTemplate('user_verification', ['user_name' => $doctor->name]);
            $link = [__('CONFIRM YOUR EMAIL') => route('doctor.verification', $doctor->email_verified_token)];
            $this->sendMail($doctor->email, $subject, $message, $link, true);
        } catch (\Exception $e) {
            info('Doctor verification mail failed for doctor ' . $doctor->id . ': ' . $e->getMessage());
            return redirect()->route('doctor.subscription.complete', ['token' => $doctor->registration_token])
                ->with(['message' => __('Your account was created, but the verification email could not be sent. Please use Resend verification or contact support.'), 'alert-type' => 'warning']);
        }

        $notification = ['message' => __('Registration successful! Please check your email to verify, then complete your package payment.'), 'alert-type' => 'success'];
        return redirect()->route('doctor.subscription.complete', ['token' => $doctor->registration_token])->with($notification);

    }
    public function custom_doctor_verification($token) {
        $doctor = Doctor::where('email_verified_token', $token)->first();
        if ($doctor) {

            if ($doctor->email_verified_at != null) {
                $notification = ['message' => __('Email already verified'), 'alert-type' => 'error'];
                return redirect()->route('login', ['type' => 'doctor'])->with($notification);
            }

            $doctor->email_verified_at = now();
            $doctor->email_verified_token = null;
            $doctor->save();

            // If the doctor still needs to complete subscription payment, send them there.
            // Otherwise (e.g. seeded/imported doctors without a registration token) go to login.
            // Already-active specialists (e.g. verifying at login) go straight to sign in.
            if ($doctor->registration_token && ! app(\Modules\Doctor\app\Services\SubscriptionAccessService::class)->activeDoctorSubscription($doctor)) {
                $notification = ['message' => __('Email verified successfully. Please complete the package payment and wait for admin approval before logging in.'), 'alert-type' => 'success'];
                return redirect()->route('doctor.subscription.complete', ['token' => $doctor->registration_token])->with($notification);
            }

            $notification = ['message' => __('Email verified successfully. You can now log in.'), 'alert-type' => 'success'];
            return redirect()->route('login', ['type' => 'doctor'])->with($notification);

        } else {
            $notification = ['message' => __('Invalid or expired verification link.'), 'alert-type' => 'error'];
            return redirect()->route('login')->with($notification);
        }
    }

    public function completeRegistration(string $token): View {
        $doctor       = Doctor::where('registration_token', $token)->firstOrFail();
        $packages     = $this->packageOptions();
        $subscription = DoctorSubscription::where('doctor_id', $doctor->id)->latest()->first();

        // Fetch Paystack public key from the BasicPayment settings table
        $paystackPublicKey = \Modules\BasicPayment\app\Models\BasicPayment::where('key', 'paystack_public_key')->value('value');
        $paystackEnabled   = \Modules\BasicPayment\app\Models\BasicPayment::where('key', 'paystack_status')->value('value') === 'active';

        return view('patient.profile.auth.doctor-subscription',
            compact('doctor', 'packages', 'subscription', 'paystackPublicKey', 'paystackEnabled') + ['paymentDetails' => SubscriptionPaymentSetting::first()]);
    }

    public function resendRegistrationVerification(string $token): RedirectResponse {
        $doctor = Doctor::where('registration_token', $token)->firstOrFail();
        if ($doctor->email_verified_at) return back()->with(['message' => __('Your email is already verified.'), 'alert-type' => 'info']);
        if (! $doctor->email_verified_token) {
            $doctor->update(['email_verified_token' => Str::random(100)]);
        }
        try {
            [$subject, $message] = $this->fetchEmailTemplate('user_verification', ['user_name' => $doctor->name]);
            $this->sendMail($doctor->email, $subject, $message, [__('CONFIRM YOUR EMAIL') => route('doctor.verification', $doctor->email_verified_token)], true);
            return back()->with(['message' => __('Verification email sent. Please check your inbox.'), 'alert-type' => 'success']);
        } catch (\Exception $e) {
            return $this->handleMailException($e);
        }
    }

    /**
     * Verify a Paystack payment reference and save the subscription automatically as approved.
     */
    public function verifyPaystackSubscription(Request $request, string $token): \Illuminate\Http\JsonResponse { abort(410, __("This checkout endpoint has been retired. Use Paystack checkout.")); }

    public function storeSubscription(Request $request, string $token): RedirectResponse { abort(410, __("This checkout endpoint has been retired. Use Paystack checkout.")); }

    private function packageOptions(): array {
        return app(SubscriptionPlanService::class)->for('doctor');
        /* return [
            [
                'id'          => 'basic',
                'name'        => __('Basic'),
                'tagline'     => __('For visibility on the platform'),
                'price'       => 1000,
                'duration'    => __('1 Month'),
                'duration_months' => 1,
                'features'    => [
                    __('Verified profile listing'),
                    __('Appears in search results'),
                    __('Location & specialty display'),
                    __('Call / Email contact'),
                ],
            ],
            [
                'id'          => 'premium',
                'name'        => __('Premium'),
                'tagline'     => __('For consistent patient inquiries'),
                'price'       => 3500,
                'duration'    => __('3 Months'),
                'duration_months' => 3,
                'features'    => [
                    __('Everything in Basic'),
                    __('Verified Specialist badge'),
                    __('Higher search ranking'),
                    __('Patient inquiry form (direct leads)'),
                    __('WhatsApp contact button'),
                    __('Profile photo + services'),
                    __('Basic analytics'),
                ],
            ],
            [
                'id'          => 'executive',
                'name'        => __('Executive'),
                'tagline'     => __('For maximum visibility & authority'),
                'price'       => 10000,
                'duration'    => __('6 Months'),
                'duration_months' => 6,
                'features'    => [
                    __('Everything in Premium'),
                    __('Top placement in category'),
                    __('Featured on homepage rotation'),
                    __('"Top Specialist" badge'),
                    __('Reviews & ratings enabled'),
                    __('Full analytics dashboard'),
                    __('Emergency availability tag'),
                    __('Multiple services listed'),
                ],
            ],
        ]; */
    }
}
