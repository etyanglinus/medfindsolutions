<?php

namespace App\Http\Controllers\Doctor;

use App\Enums\RedirectType;
use Illuminate\Http\Request;
use App\Traits\RedirectHelperTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Doctor\app\Models\Location;
use Modules\Doctor\app\Models\Department;
use Modules\Language\app\Models\Language;
use Modules\Language\app\Traits\GenerateTranslationTrait;
use Modules\Doctor\app\Services\SubscriptionAccessService;

class ProfileController extends Controller {
    use GenerateTranslationTrait, RedirectHelperTrait;

    public function __construct() {
        $this->middleware('auth:doctor');
    }

    public function edit_profile() {
        $requestedCode = request('code') ?? getSessionLanguage() ?? 'en';
        $fallbackCode = Language::query()->value('code') ?? 'en';
        $code = Language::where('code', $requestedCode)->exists() ? $requestedCode : $fallbackCode;

        $doctor = Auth::guard('doctor')->user();
        $subscriptionAccess = app(SubscriptionAccessService::class);
        if (! $subscriptionAccess->canEditPortfolio($doctor)) {
            abort(403, __('Your account is not active or your subscription is not approved and active.'));
        }
        // Imported/older accounts may not have every language row that the
        // portfolio form expects. Create the provider-owned row on demand so
        // newly activated accounts can edit their portfolio just like seeded
        // accounts without hitting a null translation error.
        $doctor->translations()->firstOrCreate(['lang_code' => $code]);
        $subscription = $subscriptionAccess->activeDoctorSubscription($doctor);
        $departments = Department::with('translation')->get();
        $locations = Location::with('translation')->whereHas('translations', fn ($query) => $query->where('name', 'Nairobi'))->get();
        $languages = allLanguages();
        $services = \Modules\Doctor\app\Models\Service::active();

        return view('doctor.profile.edit_profile', compact('doctor' ,'code', 'departments', 'locations', 'languages', 'subscription', 'services'));
    }

    public function profile_update(Request $request) {
        $doctor = Auth::guard('doctor')->user();
        $subscriptionAccess = app(SubscriptionAccessService::class);
        $subscription = $subscriptionAccess->activeDoctorSubscription($doctor);
        if (! $subscriptionAccess->canEditPortfolio($doctor) || ! $subscription) {
            return redirect()->route('login', ['type' => 'doctor'])
                ->with(['message' => __('Your profile can only be edited when your account is active and your subscription is approved and active.'), 'alert-type' => 'error']);
        }

        $fallbackCode = Language::query()->value('code') ?? 'en';
        $submittedCode = $request->input('code', $fallbackCode);
        $code = Language::where('code', $submittedCode)->exists() ? $submittedCode : $fallbackCode;

        $rules = [
            'telemedicine_available' => $subscription->hasEntitlement('telemedicine') ? 'sometimes|boolean' : 'exclude',
            'emergency_available' => $subscription->hasEntitlement('emergency') ? 'sometimes|boolean' : 'exclude',
            'reviews_enabled' => $subscription->hasEntitlement('reviews') ? 'sometimes|boolean' : 'exclude',
            'code' => 'required|exists:languages,code',
            'name'  => 'required|string|max:255',
            'email' => 'required|unique:doctors,email,' . $doctor->id,
            'phone'   => 'required',
            'whatsapp' => 'nullable|string|max:30',
            'fee'     => $subscription->hasEntitlement('consultation_fee') ? 'required|numeric|min:0' : 'exclude',
            'accepted_insurance' => $subscription->hasEntitlement('insurance') ? 'nullable|string|max:2000' : 'exclude',
            'service_ids' => $subscription->hasEntitlement('services') ? 'nullable|array' : 'exclude',
            'service_ids.*' => 'integer|exists:services,id',
            'years_of_experience' => 'required',

            'department_id'   => 'required|exists:departments,id',
            'location_id'     => 'required|exists:locations,id',
            'licensing_body'  => 'required',
            'about'           => 'required|string|max:2000',
            'address'         => 'required|string|max:5000',
            'educations'      => 'required|string|max:10000',
            'experience'      => 'required|string|max:10000',
            'qualifications'  => 'required|string|max:10000',
            'doctor_image'   => ($doctor->image ? 'nullable' : 'required').'|image|mimes:jpg,jpeg,png,webp|max:2048',
            'seo_title'       => 'nullable|string|max:1000',
            'seo_description' => 'nullable|string|max:2000',

        ];
        // Everything the package shows publicly must be filled in (no placeholders on the live page).
        $rules = app(\App\Services\ProfileRequirements::class)->rules('doctor', $doctor) + $rules;
        $customMessages = [
            'department_id.required'  => __('The department is required.'),
            'department_id.exists'    => __('The selected department is invalid.'),

            'location_id.required'    => __('The location is required.'),
            'location_id.exists'      => __('The selected location is invalid.'),

            'fee.required'            => __('Fee is required.'),
            'fee.numeric'             => __('Fee must be a numeric.'),
            'years_of_experience.required' => __('Years of experience is required.'),

            'code.required'           => __('Language is required and must be a string.'),
            'code.exists'             => __('The selected language is invalid.'),

            'seo_title.max'           => __('SEO title may not be greater than 1000 characters.'),
            'seo_title.string'        => __('SEO title must be a string.'),
            
            'seo_description.max'     => __('SEO description may not be greater than 2000 characters.'),
            'seo_description.string'  => __('SEO description must be a string.'),

            'doctor_image.required'   => __('The image is required.'),
            'doctor_image.image'      => __('The image must be an image.'),
            'doctor_image.max'        => __('The image may not be greater than 2048 kilobytes.'),

            'name.required'           => __('Name is required'),
            'name.max'                => __('The name may not be greater than 255 characters.'),
            'name.string'             => __('The name must be a string.'),

            'phone.required'          => __('Phone number is required.'),
            'email.required'          => __('Email is required.'),
            'email.unique'            => __('Email already exist'),
            'licensing_body.required' => __('Licensing body is required.'),
            'about.required'          => __('About information is required.'),
            'address.required'        => __('Address is required.'),
            'educations.required'     => __('Educations is required.'),
            'experience.required'     => __('Experience is required.'),
            'qualifications.required' => __('Qualifications is required.'),
        ];
        $validatedData = $this->validate($request, $rules, $customMessages + app(\App\Services\ProfileRequirements::class)->messages());
        $validatedData['code'] = $code;
        $nairobiLocationId = Location::whereHas('translations', fn ($query) => $query->where('name', 'Nairobi'))->value('id');
        if (! $nairobiLocationId) {
            return redirect()->back()->withInput()->with(['alert-type' => 'error', 'message' => __('The Nairobi location must be configured before updating your profile.')]);
        }
        $validatedData['location_id'] = $nairobiLocationId;

        if (! $subscription->hasEntitlement('whatsapp')) {
            unset($validatedData['whatsapp']);
        }

        $doctor->update($validatedData);
        if ($subscription->hasEntitlement('services') && array_key_exists('service_ids', $validatedData)) {
            $doctor->services()->sync($validatedData['service_ids']);
        }


        if ($doctor && $request->hasFile('doctor_image')) {
            $file_name = uploadAndOptimizeImage(
                file: $request->doctor_image,
                oldFile: $doctor->image,
            );
            $doctor->image = $file_name;
        }
        $doctor->save();

        $this->updateTranslations(
            $doctor,
            $request,
            $validatedData,
        );

        return $this->redirectWithMessage(
            RedirectType::UPDATE->value,
            'doctor.edit-profile',
            ['doctor' => $doctor->id, 'code' => $request->code]
        );
    }

    public function change_password() {
        return view('doctor.profile.change_password');
    }

    public function update_password(Request $request) {
        $doctor = Auth::guard('doctor')->user();
        $rules = [
            'current_password' => 'required',
            'password'         => 'required|confirmed|min:4',
        ];
        $customMessages = [
            'current_password.required' => __('Current password is required'),
            'password.required'         => __('Password is required'),
            'password.confirmed'        => __('Confirm password does not match'),
            'password.min'              => __('Password must be at leat 4 characters'),
        ];
        $this->validate($request, $rules, $customMessages);

        if (Hash::check($request->current_password, $doctor->password)) {
            $doctor->password = Hash::make($request->password);
            $doctor->save();

            $notification = __('Password Updated successfully');
            $notification = ['message' => $notification, 'alert-type' => 'success'];

            return $this->redirectWithMessage(RedirectType::UPDATE->value, '', [], $notification);

        } else {
            $notification = __('Current password does not match');
            $notification = ['message' => $notification, 'alert-type' => 'error'];

            return redirect()->back()->with($notification);
        }
    }
}
