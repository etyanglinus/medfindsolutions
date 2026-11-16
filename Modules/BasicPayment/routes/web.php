<?php

use Illuminate\Support\Facades\Route;
use Modules\BasicPayment\app\Http\Controllers\BasicPaymentController;

/*
| Admin settings for Paystack, the only payment gateway MedFind uses. Provider subscription
| checkout lives in routes/web.php (provider.paystack.*). The template's patient appointment
| checkout (place-order, pay-via-*, mobile "webview" payments) and the other gateways were removed.
*/
Route::group(['as' => 'admin.', 'prefix' => 'admin', 'middleware' => ['auth:admin', 'translation']], function () {
    Route::controller(BasicPaymentController::class)->group(function () {
        Route::get('basicpayment', 'basicpayment')->name('basicpayment');
        Route::put('paystack-update', 'paystack_update')->name('paystack-update');
    });
});
