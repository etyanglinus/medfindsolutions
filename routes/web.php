<?php

\Illuminate\Support\Facades\Route::get('listing-engagement/{type}/{provider}/{event}', [\App\Http\Controllers\ListingInsightsController::class, 'track'])->middleware(['signed', 'throttle:120,1'])->name('listing.track');
\Illuminate\Support\Facades\Route::get('listing-performance/{type}', [\App\Http\Controllers\ListingInsightsController::class, 'report'])->name('listing.report');
\Illuminate\Support\Facades\Route::patch('listing-inquiries/{type}/{inquiry}', [\App\Http\Controllers\ProviderInboxController::class, 'update'])->name('listing.inquiries.update');
\Illuminate\Support\Facades\Route::get('listing-inbox/{type}', [\App\Http\Controllers\ListingInsightsController::class, 'inquiries'])->name('listing.inbox');
\Illuminate\Support\Facades\Route::get('listing-receipts/{type}', [\App\Http\Controllers\ProviderReceiptController::class, 'index'])->whereIn('type', ['doctor','clinic','school','supplier'])->name('listing.receipts');
\Illuminate\Support\Facades\Route::get('listing-receipts/{type}/{transaction}', [\App\Http\Controllers\ProviderReceiptController::class, 'download'])->whereIn('type', ['doctor','clinic','school','supplier'])->whereNumber('transaction')->name('listing.receipts.download');
\Illuminate\Support\Facades\Route::get('provider/receipt/{transaction}', [\App\Http\Controllers\ProviderReceiptController::class, 'signed'])->whereNumber('transaction')->middleware(['signed', 'throttle:30,1'])->name('provider.receipt.signed');
\Illuminate\Support\Facades\Route::get('provider/email-preferences/{type}/{id}', [\App\Http\Controllers\ProviderEmailPreferenceController::class, 'show'])->whereIn('type', ['doctor','clinic','school','supplier'])->whereNumber('id')->middleware(['signed', 'throttle:30,1'])->name('provider.email-preferences');
\Illuminate\Support\Facades\Route::post('provider/email-preferences/{type}/{id}', [\App\Http\Controllers\ProviderEmailPreferenceController::class, 'update'])->whereIn('type', ['doctor','clinic','school','supplier'])->whereNumber('id')->middleware(['signed', 'throttle:30,1'])->name('provider.email-preferences.update');
\Illuminate\Support\Facades\Route::get('listing-plans/doctor', [\App\Http\Controllers\ListingInsightsController::class, 'doctorPlans'])->name('listing.doctor-plans');
\Illuminate\Support\Facades\Route::patch('listing-inbox/{type}/{inquiry}', [\App\Http\Controllers\ListingInsightsController::class, 'updateInquiry'])->name('listing.inbox.update');
// Short commit id of the code that is live, written by scripts/deploy.sh. The CI/CD pipeline polls this
// to confirm a deploy finished. Reveals nothing beyond an 8-character commit id.
\Illuminate\Support\Facades\Route::get('__release', fn () => response(trim((string) @file_get_contents(storage_path('app/deployed-release'))) ?: 'unknown', 200, ['Content-Type' => 'text/plain', 'Cache-Control' => 'no-store']))->name('release');
\Illuminate\Support\Facades\Route::get('provider/paystack/callback', [\App\Http\Controllers\Provider\PaystackCheckoutController::class, 'callback'])->name('provider.paystack.callback')->middleware('throttle:30,1');
\Illuminate\Support\Facades\Route::get('provider/{type}/renew-link/{id}', [\App\Http\Controllers\Provider\PaystackCheckoutController::class, 'renewLink'])->whereIn('type', ['doctor','clinic','school','supplier'])->whereNumber('id')->middleware(['signed', 'throttle:30,1'])->name('provider.renew-link');
\Illuminate\Support\Facades\Route::get('provider/{type}/payment-access/{id}', [\App\Http\Controllers\Provider\PaystackCheckoutController::class, 'access'])->whereIn('type', ['doctor','clinic','school','supplier'])->whereNumber('id')->middleware(['signed', 'throttle:30,1'])->name('provider.payment-access');
\Illuminate\Support\Facades\Route::post('provider/{type}/checkout/{token}', [\App\Http\Controllers\Provider\PaystackCheckoutController::class, 'start'])->whereIn('type', ['doctor','clinic','school','supplier'])->name('provider.paystack.start')->middleware('throttle:10,1');
\Illuminate\Support\Facades\Route::post('provider/{type}/renew', [\App\Http\Controllers\Provider\PaystackCheckoutController::class, 'renew'])->whereIn('type', ['doctor','clinic','school','supplier'])->name('provider.paystack.renew')->middleware('throttle:10,1');

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;

//maintenance mode route
Route::get('/maintenance-mode', function () {
    $maintenance = app(\App\Services\MaintenanceService::class);
    if (! $maintenance->isActive()) {
        return redirect()->route('home');
    }
    return response()->view('maintenance', ['until' => $maintenance->until()], 503);
})->name('maintenance.mode');

Route::get('set-language', [DashboardController::class, 'setLanguage'])->name('set-language');
Route::get('set-currency', [DashboardController::class, 'setCurrency'])->name('set-currency');

require __DIR__ . '/admin.php';
require __DIR__ . '/doctor.php';
require __DIR__ . '/patient.php';
require __DIR__ . '/website.php';

Route::fallback(function () {
    abort(404);
});
