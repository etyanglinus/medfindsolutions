<?php

use Illuminate\Support\Facades\Route;
use Modules\Medicine\app\Http\Controllers\MedicineController;
use Modules\Medicine\app\Http\Controllers\MedicineTypeController;

Route::middleware(['auth:admin', 'translation'])->name('admin.')->prefix('admin')->group(function () {
    Route::get('medicine', [MedicineController::class, 'index'])->name('medicine.index');
    Route::post('medicine', [MedicineController::class, 'store'])->name('medicine.store');
    Route::post('medicine/{id}', [MedicineController::class, 'update'])->name('medicine.update');
    Route::delete('medicine/{id}', [MedicineController::class, 'destroy'])->name('medicine.destroy');
    Route::put('/medicine/status-update/{id}', [MedicineController::class, 'statusUpdate'])->name('medicine.status-update');

    Route::get('medicine/type', [MedicineTypeController::class, 'index'])->name('medicine.type.index');
    Route::post('medicine/type/store', [MedicineTypeController::class, 'store'])->name('medicine.type.store');
    Route::post('medicine/type/{id}', [MedicineTypeController::class, 'update'])->name('medicine.type.update');
    Route::delete('medicine/type/{id}', [MedicineTypeController::class, 'destroy'])->name('medicine.type.destroy');
    Route::put('/medicine/type/status-update/{id}', [MedicineTypeController::class, 'statusUpdate'])->name('medicine.type.status-update');
});