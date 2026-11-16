<?php

namespace Modules\Medicine\app\Http\Controllers;

use App\Enums\RedirectType;
use App\Http\Controllers\Controller;
use App\Traits\RedirectHelperTrait;
use Illuminate\Http\RedirectResponse;
use Modules\Language\app\Enums\TranslationModels;
use Modules\Language\app\Traits\GenerateTranslationTrait;
use Modules\Medicine\app\Http\Requests\MedicineRequest;
use Modules\Medicine\app\Models\Medicine;

class MedicineController extends Controller {
    use GenerateTranslationTrait, RedirectHelperTrait;
    /**
     * Display a listing of the resource.
     */
    public function index() {
        $medicines = Medicine::paginate(15);
        $code = request('code') ?? getSessionLanguage();
        return view('medicine::index', compact('medicines', 'code'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MedicineRequest $request): RedirectResponse {
        checkAdminHasPermissionAndThrowException('medicine.store');
        $item = Medicine::create($request->validated());

        $this->generateTranslations(
            TranslationModels::Medicine,
            $item,
            'medicine_id',
            $request,
        );

        return $this->redirectWithMessage(RedirectType::CREATE->value);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MedicineRequest $request, $id): RedirectResponse {
        checkAdminHasPermissionAndThrowException('medicine.update');
        $validatedData = $request->validated();

        $item = Medicine::findOrFail($id);
        $item->update($validatedData);

        $this->updateTranslations($item, $request, $validatedData);

        return $this->redirectWithMessage(RedirectType::UPDATE->value);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {
        checkAdminHasPermissionAndThrowException('medicine.delete');

        $item = Medicine::findOrFail($id);

        $item->translations()->each(function ($translation) {
            $translation->medicine()->dissociate();
            $translation->delete();
        });

        $item->delete();

        return $this->redirectWithMessage(RedirectType::DELETE->value);
    }

    public function statusUpdate($id) {
        checkAdminHasPermissionAndThrowException('medicine.update');

        $item = Medicine::find($id);
        $status = $item->status == 1 ? 0 : 1;
        $item->update(['status' => $status]);

        $notification = __('Updated successfully');

        return response()->json([
            'success' => true,
            'message' => $notification,
        ]);
    }
}
