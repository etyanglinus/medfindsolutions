<?php

namespace App\Http\Controllers\Clinic\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Http\Controllers\Controller;
use App\Services\SubscriptionCouponService;
use Modules\Doctor\app\Models\Clinic;
use Modules\Doctor\app\Models\ClinicSubscription;
use Modules\Doctor\app\Models\SubscriptionPaymentSetting;
use Modules\Doctor\app\Services\SubscriptionPlanService;
use Modules\Doctor\app\Services\SubscriptionAccessService;
use Modules\BasicPayment\app\Models\BasicPayment;

class RegisteredController extends Controller {

    public function create(): RedirectResponse {
        // Clinic registration lives on the shared provider-register page.
        return redirect()->route('provider.register', ['type' => 'clinic']);
    }

    public function store(Request $request): RedirectResponse {
        // Frees the email if an earlier sign-up with it was never paid/activated.
        app(\App\Services\UnfinishedSignupService::class)->clearForRegistration('clinic', $request->input('email'));
        $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'specialty' => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:clinics,email'],
            'phone'     => ['required', 'string', 'max:30'],
            'password'  => ['required', 'confirmed', 'min:4', 'max:100'],
        ], [
            'name.required'      => __('Clinic name is required.'),
            'specialty.required' => __('Specialty is required.'),
            'email.required'     => __('Email is required.'),
            'email.unique'       => __('An account with this email already exists. Please log in, or use "Forgot password" if you cannot remember your password.'),
            'phone.required'     => __('Phone number is required.'),
            'password.required'  => __('Password is required.'),
            'password.confirmed' => __('Confirm password does not match.'),
            'password.min'       => __('Password must be at least 4 characters.'),
        ]);

        DB::beginTransaction();
        try {
            $slug = Str::slug($request->name);
            if (Clinic::withTrashed()->whereSlug($slug)->exists()) {
                $slug = $slug . '-' . Str::random(5);
            }

            $clinic = Clinic::create([
                'name'               => $request->name,
                'slug'               => $slug,
                'specialty'          => $request->specialty,
                'email'              => $request->email,
                'phone'              => $request->phone,
                'status'             => 0,
                'password'           => Hash::make($request->password),
                'registration_token' => Str::random(100),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            info('Clinic registration failed: ' . $e->getMessage());
            return redirect()
                ->route('provider.register', ['type' => 'clinic'])
                ->withInput()
                ->with(['message' => __('Registration failed. Please try again.'), 'alert-type' => 'error']);
        }

        return redirect()
            ->route('clinic.subscription.complete', ['token' => $clinic->registration_token])
            ->with(['message' => __('Account created! Please choose a subscription package to complete your listing.'), 'alert-type' => 'success']);
    }

    public function completeRegistration(string $token): View {
        $clinic   = Clinic::where('registration_token', $token)->firstOrFail();
        $packages = $this->packageOptions();
        $subscription = ClinicSubscription::where('clinic_id', $clinic->id)->latest()->first();
        $paystackPublicKey = \Modules\BasicPayment\app\Models\BasicPayment::where('key', 'paystack_public_key')->value('value');
        $paystackEnabled = \Modules\BasicPayment\app\Models\BasicPayment::where('key', 'paystack_status')->value('value') === 'active';

        return view('patient.profile.auth.clinic-subscription', compact('clinic', 'packages', 'subscription', 'paystackPublicKey', 'paystackEnabled') + ['paymentDetails' => SubscriptionPaymentSetting::first()]);
    }

    public function verifyPaystackSubscription(Request $request, string $token): \Illuminate\Http\JsonResponse { abort(410, __("This checkout endpoint has been retired. Use Paystack checkout.")); }

    public function storeSubscription(Request $request, string $token): RedirectResponse { abort(410, __("This checkout endpoint has been retired. Use Paystack checkout.")); }

    public function renewSubscription(Request $request): RedirectResponse
    { abort(410, __("This checkout endpoint has been retired. Use Paystack checkout.")); }

    public function verifyRenewalPaystack(Request $request): \Illuminate\Http\JsonResponse
    { abort(410, __("This checkout endpoint has been retired. Use Paystack checkout.")); }

    public function verifyEmail(string $token): \Illuminate\Http\RedirectResponse {
        $clinic = \Modules\Doctor\app\Models\Clinic::where('email_verified_token', $token)->first();

        if (! $clinic) {
            return redirect()->route('login', ['type' => 'clinic'])
                ->with(['message' => __('Invalid or expired verification link.'), 'alert-type' => 'error']);
        }

        if ($clinic->email_verified_at) {
            return redirect()->route('login', ['type' => 'clinic'])
                ->with(['message' => __('Email already verified. Please log in.'), 'alert-type' => 'info']);
        }

        $clinic->email_verified_at    = now();
        $clinic->email_verified_token = null;
        $clinic->save();

        return redirect()->route('login', ['type' => 'clinic'])
            ->with(['message' => __('Email verified successfully! You can now log in.'), 'alert-type' => 'success']);
    }

    public static function packageOptions(): array {
        return app(SubscriptionPlanService::class)->for('clinic');
        /* return [
            [
                'id'              => 'basic',
                'name'            => __('Basic'),
                'tagline'         => __('Get listed on the platform'),
                'price'           => 3000,
                'duration'        => __('1 Month'),
                'duration_months' => 1,
                'features'        => [
                    __('Clinic / Centre Name'),
                    __('Specialty display'),
                    __('Clinic Logo'),
                    __('Phone Number'),
                    __('Up to 3 Services Listed'),
                ],
            ],
            [
                'id'              => 'premium',
                'name'            => __('Premium'),
                'tagline'         => __('For consistent patient inquiries'),
                'price'           => 10000,
                'duration'        => __('3 Months'),
                'duration_months' => 3,
                'features'        => [
                    __('Everything in Basic'),
                    __('Cover Photo'),
                    __('Email Address listed'),
                    __('WhatsApp Number'),
                    __('Clinic Description (up to 100 words)'),
                    __('Insurance Accepted'),
                    __('Up to 10 Services'),
                    __('Up to 5 Clinic Highlights'),
                    __('Inquiry / Appointment Contact'),
                ],
            ],
            [
                'id'              => 'executive',
                'name'            => __('Executive'),
                'tagline'         => __('Maximum visibility & authority'),
                'price'           => 15000,
                'duration'        => __('6 Months'),
                'duration_months' => 6,
                'features'        => [
                    __('Everything in Premium'),
                    __('Up to 15 Gallery Photos'),
                    __('Up to 20 Services'),
                    __('Equipment & Technology Listed'),
                    __('Patient Reviews & Ratings'),
                    __('Emergency Availability Tag'),
                    __('24-Hour Emergency Services'),
                    __('Operating Hours'),
                    __('Google Maps Link'),
                    __('Additional Branch Locations'),
                    __('⭐ Top Clinic Badge'),
                ],
            ],
        ]; */
    }
}
