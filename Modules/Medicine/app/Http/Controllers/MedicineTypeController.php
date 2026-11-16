<?php

namespace Modules\Medicine\app\Http\Controllers;

use App\Enums\RedirectType;
use App\Http\Controllers\Controller;
use App\Traits\RedirectHelperTrait;
use Illuminate\Http\RedirectResponse;
use Modules\Language\app\Enums\TranslationModels;
use Modules\Language\app\Traits\GenerateTranslationTrait;
use Modules\Medicine\app\Http\Requests\MedicineTypeRequest;
use Modules\Medicine\app\Models\MedicineType;

class MedicineTypeController extends Controller {
    use GenerateTranslationTrait, RedirectHelperTrait;
    /**
     * Display a listing of the resource.
     */
    public function index() {
        $types = MedicineType::paginate(15);
        $code = request('code') ?? getSessionLanguage();
        return view('medicine::type',compact('types','code'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MedicineTypeRequest $request): RedirectResponse {
        checkAdminHasPermissionAndThrowException('medicine.type.store');
        $item = MedicineType::create($request->validated());

        $this->generateTranslations(
            TranslationModels::MedicineType,
            $item,
            'medicine_type_id',
            $request,
        );

        return $this->redirectWithMessage(RedirectType::CREATE->value);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MedicineTypeRequest $request, $id): RedirectResponse {
        checkAdminHasPermissionAndThrowException('medicine.type.update');
        $validatedData = $request->validated();

        $item = MedicineType::findOrFail($id);
        $item->update($validatedData);

        $this->updateTranslations($item, $request, $validatedData);

        return $this->redirectWithMessage(RedirectType::UPDATE->value);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {
        checkAdminHasPermissionAndThrowException('medicine.type.delete');

        $item = MedicineType::findOrFail($id);

        $item->translations()->each(function ($translation) {
            $translation->medicine_type()->dissociate();
            $translation->delete();
        });

        $item->delete();

        return $this->redirectWithMessage(RedirectType::DELETE->value);
    }

    public function statusUpdate($id) {
        checkAdminHasPermissionAndThrowException('medicine.type.update');

        $item = MedicineType::find($id);
        $status = $item->status == 1 ? 0 : 1;
        $item->update(['status' => $status]);

        $notification = __('Updated successfully');

        return response()->json([
            'success' => true,
            'message' => $notification,
        ]);
    }
}
