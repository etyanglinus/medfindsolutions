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
use Illuminate\Support\Str;
use Modules\Doctor\app\Http\Requests\FacilityRequest;
use Modules\Doctor\app\Models\Department;
use Modules\Doctor\app\Models\Facility;
use Modules\Doctor\app\Models\Location;
use Modules\Doctor\app\Models\LicensingBody;
use Modules\Doctor\app\Models\InsuranceProvider;
use Modules\Doctor\app\Models\Service;
use Illuminate\Support\Arr;
use Modules\Language\app\Enums\TranslationModels;
use Modules\Language\app\Models\Language;
use Modules\Language\app\Traits\GenerateTranslationTrait;

class FacilityController extends Controller {
    use GenerateTranslationTrait, RedirectHelperTrait, GlobalMailTrait;

    public function index(Request $request) {
        checkAdminHasPermissionAndThrowException('facility.view');
        $query = Facility::query();

        $query->when($request->filled('keyword'), function ($qa) use ($request) {
            $qa->whereHas('translations', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->keyword . '%');
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
            $q->where('status', $request->status);
        });

        $orderBy = $request->filled('order_by') && $request->order_by == 1 ? 'asc' : 'desc';

        if ($request->filled('par-page')) {
            $facilities = $request->get('par-page') == 'all' ? $query->orderBy('id', $orderBy)->get() : $query->orderBy('id', $orderBy)->paginate($request->get('par-page'))->withQueryString();
        } else {
            $facilities = $query->orderBy('id', $orderBy)->paginate()->withQueryString();
        }

        return view('doctor::facility.index', compact('facilities'));
    }

    public function create() {
        checkAdminHasPermissionAndThrowException('facility.create');
        $departments = Department::with('translation')->active()->get();
        $locations = Location::with('translation')->active()->get();

        $licensingBodies = LicensingBody::activeFacilityBodies();
        $insuranceProviders = InsuranceProvider::active();
        $services = Service::active();

        return view('doctor::facility.create', compact('departments', 'locations', 'licensingBodies', 'insuranceProviders', 'services'));
    }

    public function store(FacilityRequest $request): RedirectResponse {
        checkAdminHasPermissionAndThrowException('facility.store');
        try {
            DB::beginTransaction();
            $validated = $request->validated();
            $validated['slug'] = $this->uniqueSlug($validated['name']);
            $validated['location_id'] = Location::whereHas('translations', fn ($query) => $query->where('name', 'Nairobi'))->value('id');
            if (! $validated['location_id']) {
                throw new \RuntimeException(__('A Nairobi location must be configured before adding a facility.'));
            }
            // remove helper ids before creating main model
            unset($validated['licensing_body_id']);
            unset($validated['insurance_provider_ids']);
            $facility = Facility::create($validated);

            if ($facility && $request->hasFile('facility_image')) {
                $file_name = uploadAndOptimizeImage(
                    file: $request->facility_image,
                    resize: [500, 500]
                );
                $facility->image = $file_name;
            }
            $facility->save();

            // sync services
            if ($request->filled('service_ids')) {
                $facility->services()->sync($request->input('service_ids'));
            }

            // Merge selected licensing body name and insurance names for translations
            $licensingBodyName = LicensingBody::find($request->licensing_body_id)?->name;
            $insuranceNames = InsuranceProvider::whereIn('id', $request->input('insurance_provider_ids', []))
                ->orderBy('name')
                ->pluck('name')
                ->toArray();

            $request->merge([
                'licensing_body'     => $licensingBodyName,
                'accepted_insurance' => $insuranceNames ? implode(', ', $insuranceNames) : null,
            ]);

            $this->generateTranslations(
                TranslationModels::Facility,
                $facility,
                'facility_id',
                $request,
            );

            DB::commit();

            return $this->redirectWithMessage(
                RedirectType::CREATE->value,
                'admin.facility.edit',
                [
                    'facility' => $facility->id,
                    'code'     => allLanguages()->first()->code,
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleMailException($e);
        }
    }

    public function edit($id) {
        checkAdminHasPermissionAndThrowException('facility.edit');
        $code = request('code') ?? getSessionLanguage();
        if (!Language::where('code', $code)->exists()) {
            abort(404);
        }
        $facility = Facility::findOrFail($id);
        if (filled($validatedData['name'] ?? null) && $validatedData['name'] !== $facility->name) {
            $validatedData['slug'] = $this->uniqueSlug($validatedData['name'], $facility->id);
        }
        $departments = Department::with('translation')->get();
        $locations = Location::with('translation')->get();
        $services = Service::active();
        $languages = allLanguages();
        $licensingBodies = LicensingBody::activeFacilityBodies();
        $insuranceProviders = InsuranceProvider::active();

        return view('doctor::facility.edit', compact('facility', 'code', 'departments', 'locations', 'languages', 'licensingBodies', 'insuranceProviders', 'services'));
    }

    public function update(FacilityRequest $request, $id) {
        checkAdminHasPermissionAndThrowException('facility.update');
        $validatedData = $request->validated();
        $validatedData['location_id'] = Location::whereHas('translations', fn ($query) => $query->where('name', 'Nairobi'))->value('id');
        if (! $validatedData['location_id']) {
            return redirect()->back()->withInput()->with(['alert-type' => 'error', 'message' => __('A Nairobi location must be configured before updating a facility.')]);
        }

        $facility = Facility::findOrFail($id);
        // remove helper ids from main table update
        $updateData = $validatedData;
        unset($updateData['licensing_body_id']);
        unset($updateData['insurance_provider_ids']);
        $facility->update($updateData);

        if ($request->hasFile('facility_image')) {
            $file_name = uploadAndOptimizeImage(
                file: $request->facility_image,
                oldFile: $facility->image,
                resize: [500, 500]
            );
            $facility->image = $file_name;
        }
        $facility->save();

        // Merge selected licensing body name and insurance names for translations before updating translations
        $licensingBodyName = LicensingBody::find($request->licensing_body_id)?->name;
        $insuranceNames = InsuranceProvider::whereIn('id', $request->input('insurance_provider_ids', []))
            ->orderBy('name')
            ->pluck('name')
            ->toArray();

        $request->merge([
            'licensing_body'     => $licensingBodyName ?? $request->input('licensing_body'),
            'accepted_insurance' => $insuranceNames ? implode(', ', $insuranceNames) : $request->input('accepted_insurance'),
        ]);

        $this->updateTranslations(
            $facility,
            $request,
            $validatedData,
        );

        // sync services
        if ($request->has('service_ids')) {
            $facility->services()->sync($request->input('service_ids', []));
        }

        return $this->redirectWithMessage(
            RedirectType::UPDATE->value,
            'admin.facility.edit',
            ['facility' => $facility->id, 'code' => $request->code]
        );
    }

    public function destroy($id) {
        checkAdminHasPermissionAndThrowException('facility.delete');

        $facility = Facility::findOrFail($id);
        if ($facility->image) {
            if (File::exists(public_path($facility->image))) {
                unlink(public_path($facility->image));
            }
        }

        $facility->translations()->delete();
        $facility->delete();

        return $this->redirectWithMessage(RedirectType::DELETE->value, 'admin.facility.index');
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string {
        $base = Str::limit(Str::slug($name), 240, '');
        $slug = $base ?: Str::random(12);
        $suffix = 2;

        while (Facility::query()->where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }

    public function statusUpdate($id) {
        checkAdminHasPermissionAndThrowException('facility.update');

        $facility = Facility::find($id);
        $status = $facility->status == 1 ? 0 : 1;
        $facility->update(['status' => $status]);

        $notification = __('Updated successfully');

        return response()->json([
            'success' => true,
            'message' => $notification,
        ]);
    }

    public function show($id) {
        checkAdminHasPermissionAndThrowException('facility.view');
        $facility = Facility::with([
            'translation',
            'department.translation',
            'location.translation',
            'services',
        ])->findOrFail($id);

        return view('doctor::facility.show', compact('facility'));
    }
}
