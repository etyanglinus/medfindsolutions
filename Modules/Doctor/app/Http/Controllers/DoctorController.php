<?php

namespace Modules\Doctor\app\Http\Controllers;

use App\Enums\RedirectType;
use App\Http\Controllers\Controller;
use App\Traits\GlobalMailTrait;
use App\Traits\RedirectHelperTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Doctor\app\Http\Requests\DoctorRequest;
use Modules\Doctor\app\Models\Department;
use Modules\Doctor\app\Models\Doctor;
use Modules\Doctor\app\Models\Location;
use Modules\Doctor\app\Models\Service;
use Modules\Doctor\app\Models\LicensingBody;
use Modules\Doctor\app\Models\InsuranceProvider;
use Modules\Doctor\app\Models\SubscriptionPackage;
use Modules\Language\app\Enums\TranslationModels;
use Modules\Language\app\Models\Language;
use Modules\Language\app\Traits\GenerateTranslationTrait;

class DoctorController extends Controller {
    use GenerateTranslationTrait, RedirectHelperTrait, GlobalMailTrait;

    public function index(Request $request) {
        checkAdminHasPermissionAndThrowException('doctor.view');
        $query = Doctor::with(['department', 'services', 'latestSubscription']);

        $query->when($request->filled('keyword'), function ($qa) use ($request) {
            $qa->whereHas('translations', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->keyword . '%');
                $q->orWhere('designations', 'like', '%' . $request->keyword . '%');
                $q->orWhere('licensing_body', 'like', '%' . $request->keyword . '%');
                $q->orWhere('about', 'like', '%' . $request->keyword . '%');
                $q->orWhere('address', 'like', '%' . $request->keyword . '%');
                $q->orWhere('educations', 'like', '%' . $request->keyword . '%');
                $q->orWhere('experience', 'like', '%' . $request->keyword . '%');
                $q->orWhere('qualifications', 'like', '%' . $request->keyword . '%');
            });
        });

        $query->when($request->filled('show_homepage'), function ($q) use ($request) {
            $q->where('show_homepage', $request->show_homepage);
        });

        $query->when($request->filled('status'), function ($q) use ($request) {
            if ($request->status == 1) {
                $q->where('status', $request->status)->verify();
            } else {
                $q->where('status', $request->status)->orWhere('email_verified_at', null);
            }
        });

        $query->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id));
        $query->when($request->filled('plan_code'), fn ($q) => $q->whereHas('subscriptions', fn ($subscription) => $subscription->where('plan_code', $request->plan_code)));

        $orderBy = $request->filled('order_by') && $request->order_by == 1 ? 'asc' : 'desc';

        if ($request->filled('par-page')) {
            $doctors = $request->get('par-page') == 'all' ? $query->orderBy('id', $orderBy)->get() : $query->orderBy('id', $orderBy)->paginate($request->get('par-page'))->withQueryString();
        } else {
            $doctors = $query->orderBy('id', $orderBy)->paginate()->withQueryString();
        }

        $departments = Department::with('translation')->active()->orderBy('id')->get();
        $plans = SubscriptionPackage::where('provider_type', 'doctor')->where('is_active', true)->orderBy('price')->get();
        return view('doctor::doctor.index', compact('doctors', 'departments', 'plans'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {
        checkAdminHasPermissionAndThrowException('doctor.create');
        $departments = Department::with('translation')->active()->get();
        $locations = Location::with('translation')->active()->get();

        $services = Service::active();
        $licensingBodies = LicensingBody::activeDoctorBodies();
        $insuranceProviders = InsuranceProvider::active();

        return view('doctor::doctor.create', compact('departments', 'locations', 'services', 'licensingBodies', 'insuranceProviders'));
    }

    public function store(DoctorRequest $request): RedirectResponse {
        checkAdminHasPermissionAndThrowException('doctor.store');
        try {
            DB::beginTransaction();
            $validated = $request->validated();
            $validated['slug'] = $this->uniqueSlug($validated['name']);
            $validated['location_id'] = Location::whereHas('translations', fn ($query) => $query->where('name', 'Nairobi'))->value('id');
            if (! $validated['location_id']) {
                throw new \RuntimeException(__('A Nairobi location must be configured before adding a doctor.'));
            }
            $doctor = Doctor::create($validated);

            $doctor->password = Hash::make($request->password);

            [$subject, $message] = $this->fetchEmailTemplate('doctor_login', ['doctor_name' => $doctor->name, 'email' => $doctor->email, 'password' => $request->password]);
            if (app()->isProduction()) {
                $this->sendMail($doctor->email, $subject, $message);
            }

            if ($doctor && $request->hasFile('doctor_image')) {
                $file_name = uploadAndOptimizeImage(
                    file: $request->doctor_image,
                    resize: [500, 500]
                );
                $doctor->image = $file_name;
            }
            $doctor->email_verified_at = now();
            $doctor->save();

            // sync services
            if ($request->filled('service_ids')) {
                $doctor->services()->sync($request->input('service_ids'));
            }

            $this->generateTranslations(
                TranslationModels::Doctor,
                $doctor,
                'doctor_id',
                $request,
            );

            DB::commit();

            return $this->redirectWithMessage(
                RedirectType::CREATE->value,
                'admin.doctor.edit',
                [
                    'doctor' => $doctor->id,
                    'code'   => allLanguages()->first()->code,
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleMailException($e);
        }
    }

    public function edit($id) {
        checkAdminHasPermissionAndThrowException('doctor.edit');
        $code = request('code') ?? getSessionLanguage();
        if (!Language::where('code', $code)->exists()) {
            abort(404);
        }
        $doctor = Doctor::findOrFail($id);
        $departments = Department::with('translation')->get();
        $locations = Location::with('translation')->get();
        $services = Service::active();
        $licensingBodies = LicensingBody::activeDoctorBodies();
        $insuranceProviders = InsuranceProvider::active();
        $languages = allLanguages();

        return view('doctor::doctor.edit', compact('doctor', 'code', 'departments', 'locations', 'languages', 'services', 'licensingBodies', 'insuranceProviders'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DoctorRequest $request, $id) {
        checkAdminHasPermissionAndThrowException('doctor.update');
        $validatedData = $request->validated();
        $validatedData['location_id'] = Location::whereHas('translations', fn ($query) => $query->where('name', 'Nairobi'))->value('id');
        if (! $validatedData['location_id']) {
            return redirect()->back()->withInput()->with(['alert-type' => 'error', 'message' => __('A Nairobi location must be configured before updating a doctor.')]);
        }

        $doctor = Doctor::findOrFail($id);

        if (filled($validatedData['name'] ?? null) && $validatedData['name'] !== $doctor->name) {
            $validatedData['slug'] = $this->uniqueSlug($validatedData['name'], $doctor->id);
        }

        $doctor->update($validatedData);

        if ($request->has('password')) {
            $doctor->password = Hash::make($request->password);
        }

        if ($doctor && $request->hasFile('doctor_image')) {
            $file_name = uploadAndOptimizeImage(
                file: $request->doctor_image,
                oldFile: $doctor->image,
                resize: [500, 500]
            );
            $doctor->image = $file_name;
        }
        $doctor->save();

        $this->updateTranslations(
            $doctor,
            $request,
            $validatedData,
        );

        // sync services
        if ($request->has('service_ids')) {
            $doctor->services()->sync($request->input('service_ids', []));
        }

        return $this->redirectWithMessage(
            RedirectType::UPDATE->value,
            'admin.doctor.edit',
            ['doctor' => $doctor->id, 'code' => $request->code]
        );
    }

    public function destroy($id) {
        checkAdminHasPermissionAndThrowException('doctor.delete');

        $doctor = Doctor::findOrFail($id);

        if ($doctor->appointments()->count() > 0) {
            return redirect()->back()->with(['alert-type' => 'error', 'message' => __('Cannot delete, doctor has appointments.')]);
        }
        $doctor->translations()->each(function ($translation) {
            $translation->doctor()->dissociate();
            $translation->delete();
        });

        if ($doctor->image) {
            if (File::exists(public_path($doctor->image))) {
                unlink(public_path($doctor->image));
            }
        }

        $doctor->delete();

        return $this->redirectWithMessage(RedirectType::DELETE->value, 'admin.doctor.index');
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string {
        $base = Str::limit(Str::slug($name), 240, '');
        $slug = $base ?: Str::random(12);
        $suffix = 2;

        while (Doctor::query()->where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }

    public function statusUpdate($id) {
        checkAdminHasPermissionAndThrowException('doctor.update');

        $doctor = Doctor::find($id);
        $status = $doctor->status == 1 ? 0 : 1;
        $doctor->update(['status' => $status]);

        $notification = __('Updated successfully');

        return response()->json([
            'success' => true,
            'message' => $notification,
        ]);
    }

    public function send_verify_request($id) {
        try {
            DB::beginTransaction();
            $doctor = Doctor::where('email_verified_at', null)->find($id);
            if ($doctor) {
                $doctor->email_verified_token = Str::random(100);
                $doctor->save();

                [$subject, $message] = $this->fetchEmailTemplate('user_verification', ['user_name' => $doctor->name]);
                $link = [__('CONFIRM YOUR EMAIL') => route('doctor.verification', $doctor->email_verified_token)];
                $this->sendMail($doctor->email, $subject, $message, $link);

                $notification = __('A verification link has been send to user mail');
                $notification = ['message' => $notification, 'alert-type' => 'success'];
            } else {
                $notification = __('Email already verified');
                $notification = ['message' => $notification, 'alert-type' => 'warning'];
            }
            return redirect()->back()->with($notification);
        } catch (\Exception $e) {
            DB::rollBack();
            info($e->getMessage());
            return $this->handleMailException($e);
        }
    }

    public function send_verify_request_to_all() {
        try {
            DB::beginTransaction();
            $doctors = Doctor::where('email_verified_at', null)->get();
            if ($doctors->count()) {
                foreach ($doctors as $doctor) {
                    $doctor->email_verified_token = Str::random(100);
                    $doctor->save();
    
                    [$subject, $message] = $this->fetchEmailTemplate('user_verification', ['user_name' => $doctor->name]);
                    $link = [__('CONFIRM YOUR EMAIL') => route('doctor.verification', $doctor->email_verified_token)];
                    $this->sendMail($doctor->email, $subject, $message, $link);
                }
                $notification = __('A verification link has been send to user mail');
                $notification = ['message' => $notification, 'alert-type' => 'success'];
            } else {
                $notification = __('Email already verified');
                $notification = ['message' => $notification, 'alert-type' => 'warning'];
            }

            return redirect()->back()->with($notification);
        } catch (\Exception $e) {
            DB::rollBack();
            info($e->getMessage());
            return $this->handleMailException($e);
        }
    }

    public function show($id) {
        checkAdminHasPermissionAndThrowException('doctor.view');
        $doctor = Doctor::with([
            'translation',
            'department.translation',
            'location.translation',
            'socialMedia',
        ])->findOrFail($id);

        return view('doctor::doctor.show', compact('doctor'));
    }
}
