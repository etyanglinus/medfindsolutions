<?php

use Illuminate\Support\Facades\Route;
use Modules\Habit\app\Http\Controllers\HabitController;

Route::middleware(['auth:admin', 'translation'])->name('admin.')->prefix('admin')->group(function () {
    Route::get('habit', [HabitController::class, 'index'])->name('habit.index');
    Route::post('habit', [HabitController::class, 'store'])->name('habit.store');
    Route::post('habit/{id}', [HabitController::class, 'update'])->name('habit.update');
    Route::delete('habit/{id}', [HabitController::class, 'destroy'])->name('habit.destroy');
    Route::put('/habit/status-update/{id}', [HabitController::class, 'statusUpdate'])->name('habit.status-update');
});