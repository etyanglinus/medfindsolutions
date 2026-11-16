<?php

namespace Modules\Habit\app\Http\Controllers;

use App\Enums\RedirectType;
use App\Http\Controllers\Controller;
use App\Traits\RedirectHelperTrait;
use Illuminate\Http\RedirectResponse;
use Modules\Habit\app\Http\Requests\HabitRequest;
use Modules\Habit\app\Models\Habit;
use Modules\Language\app\Enums\TranslationModels;
use Modules\Language\app\Traits\GenerateTranslationTrait;

class HabitController extends Controller {
    use GenerateTranslationTrait, RedirectHelperTrait;
    /**
     * Display a listing of the resource.
     */
    public function index() {
        $habits = Habit::paginate(15);
        $code = request('code') ?? getSessionLanguage();
        return view('habit::index', compact('habits', 'code'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(HabitRequest $request): RedirectResponse {
        checkAdminHasPermissionAndThrowException('habit.store');
        $item = Habit::create($request->validated());

        $this->generateTranslations(
            TranslationModels::Habit,
            $item,
            'habit_id',
            $request,
        );

        return $this->redirectWithMessage(RedirectType::CREATE->value);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(HabitRequest $request, $id): RedirectResponse {
        checkAdminHasPermissionAndThrowException('habit.update');
        $validatedData = $request->validated();

        $item = Habit::findOrFail($id);
        $item->update($validatedData);

        $this->updateTranslations($item, $request, $validatedData);

        return $this->redirectWithMessage(RedirectType::UPDATE->value);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {
        checkAdminHasPermissionAndThrowException('habit.delete');

        $item = Habit::findOrFail($id);

        $item->translations()->each(function ($translation) {
            $translation->habit()->dissociate();
            $translation->delete();
        });

        $item->delete();

        return $this->redirectWithMessage(RedirectType::DELETE->value);
    }

    public function statusUpdate($id) {
        checkAdminHasPermissionAndThrowException('habit.update');

        $item = Habit::find($id);
        $status = $item->status == 1 ? 0 : 1;
        $item->update(['status' => $status]);

        $notification = __('Updated successfully');

        return response()->json([
            'success' => true,
            'message' => $notification,
        ]);
    }
}
