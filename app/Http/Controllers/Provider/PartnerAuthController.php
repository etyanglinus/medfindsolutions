<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Models\ProviderSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Doctor\app\Services\SubscriptionPlanService;
use Modules\HealthcareInstitution\app\Models\HealthcareInstitution;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;

class PartnerAuthController extends Controller
{
    public function registerForm(string $type): RedirectResponse
    {
        $this->definition($type);
        return redirect()->route('provider.register', ['type' => $type]);
    }

    public function register(Request $request, string $type): RedirectResponse
    {
        $definition = $this->definition($type);
        $table = $type === 'school' ? 'healthcare_institutions' : 'medical_suppliers';
        // Frees the email if an earlier sign-up with it was never paid/activated.
        app(\App\Services\UnfinishedSignupService::class)->clearForRegistration($type, $request->input('email'));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique($table, 'official_email')],
            'phone' => ['required', 'string', 'max:40'],
            'password' => ['required', 'confirmed', 'min:8', 'max:100'],
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'email.unique' => __('An account with this email already exists. Please log in, or use "Forgot password" if you cannot remember your password.'),
            'logo.required' => __('Please upload your logo.'),
            'logo.image' => __('The logo must be an image (JPG, PNG or WebP).'),
            'logo.mimes' => __('The logo must be a JPG, PNG or WebP image.'),
            'logo.max' => __('The logo must be 2 MB or smaller.'),
        ]);
        // Required at sign-up: every listing has a logo from day one (same storage as the profile page).
        $logoPath = $request->file('logo')->storePublicly('partner-logos', 'public');

        $record = DB::transaction(function () use ($validated, $definition, $type, $logoPath) {
            $model = $definition['model'];
            $record = $model::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']).'-'.Str::lower(Str::random(10)),
                'official_email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'registration_token' => Str::random(80),
                'email_verified_token' => Str::random(80),
                'logo' => $logoPath,
            ]);

            $profile = ProviderProfile::create([
                'provider_type' => $definition['profile_type'],
                'provider_id' => $record->id,
                'status' => ProviderProfile::STATUS_PENDING_PAYMENT,
                'verification_status' => ProviderProfile::VERIFICATION_UNVERIFIED,
            ]);

            $record->update(['provider_profile_id' => $profile->id]);

            return $record;
        });

        try {
            $this->sendVerification($record, $type);
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('website.partner.subscription', ['type' => $type, 'token' => $record->registration_token])
                ->with(['message' => __('Your account was created, but verification email delivery failed. Please request another verification email from the login page.'), 'alert-type' => 'warning']);
        }
        return redirect()->route('website.partner.subscription', ['type' => $type, 'token' => $record->registration_token]);
    }

    public function loginForm(string $type): RedirectResponse
    {
        $this->definition($type);
        return redirect()->route('login', ['type' => $type]);
    }

    public function login(Request $request, string $type): RedirectResponse
    {
        $definition = $this->definition($type);
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);

        // Accepts (once) a password saved through the old input filter, then stores the real one.
        $record = auth($definition['guard'])->getProvider()->retrieveByCredentials(['official_email' => $credentials['email']]);
        if ($record) \App\Support\PasswordInput::verify($record, $credentials['password']);
        if (! auth($definition['guard'])->attempt(['official_email' => $credentials['email'], 'password' => $credentials['password']])) {
            return back()->withErrors(['email' => __('The supplied credentials are incorrect.')])->withInput($request->only('email'));
        }

        if (! auth($definition['guard'])->user()->email_verified_at) {
            auth($definition['guard'])->logout();
            return back()->withErrors(['email' => __('Please verify your email address before logging in.')]);
        }
        $request->session()->regenerate();
        return redirect()->route('website.partner.dashboard', ['type' => $type]);
    }

    public function logout(Request $request, string $type): RedirectResponse
    {
        $definition = $this->definition($type);
        auth($definition['guard'])->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('website.partner.login', ['type' => $type]);
    }

    public function dashboard(string $type): View
    {
        $definition = $this->definition($type);
        abort_unless(auth($definition['guard'])->check(), 403);
        $record = auth($definition['guard'])->user();
        $profile = ProviderProfile::where('provider_type', $definition['profile_type'])->where('provider_id', $record->id)->firstOrFail();
        $subscription = $profile->subscriptions()->latest()->first();
        return view('provider.partner-dashboard', compact('type', 'record', 'profile', 'subscription'));
    }

    private function sendVerification($record, string $type): void
    {
        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute('website.partner.verification.verify', now()->addHours(24), ['type' => $type, 'token' => $record->email_verified_token]);
        \Illuminate\Support\Facades\Mail::to($record->official_email)->send(new \App\Mail\PartnerEmailVerification($url, self::definition($type)['label']));
    }

    public function resendVerification(Request $request, string $type): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $model = self::definition($type)['model'];
        $record = $model::where('official_email', $data['email'])->first();
        if ($record && ! $record->email_verified_at) {
            if (! $record->email_verified_token) $record->update(['email_verified_token' => Str::random(80)]);
            try { $this->sendVerification($record, $type); }
            catch (\Exception $e) {
                report($e);
                return back()->with(['message' => __('Verification email could not be sent. Please contact support.'), 'alert-type' => 'error']);
            }
        }
        return back()->with(['message' => __('If your account needs verification, a verification email has been sent.'), 'alert-type' => 'success']);
    }

    public function verifyEmail(string $type, string $token): RedirectResponse
    {
        $model = self::definition($type)['model'];
        $record = $model::where('email_verified_token', $token)->firstOrFail();
        $record->update(['email_verified_at' => now(), 'email_verified_token' => null]);
        return redirect()->route('website.partner.login', $type)->with(['message' => __('Email verified successfully. You can now log in.'), 'alert-type' => 'success']);
    }

    public static function definition(string $type): array
    {
        return match ($type) {
            'school' => ['model' => HealthcareInstitution::class, 'guard' => 'school', 'profile_type' => ProviderProfile::TYPE_SCHOOL, 'label' => __('Healthcare School')],
            'supplier' => ['model' => MedicalSupplier::class, 'guard' => 'supplier', 'profile_type' => ProviderProfile::TYPE_SUPPLIER, 'label' => __('Medical Supplier')],
            default => abort(404),
        };
    }
}
