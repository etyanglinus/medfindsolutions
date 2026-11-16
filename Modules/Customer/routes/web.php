<?php

use Illuminate\Support\Facades\Route;
use Modules\Customer\app\Http\Controllers\CustomerController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['as' => 'admin.', 'prefix' => 'admin', 'middleware' => ['auth:admin', 'translation']], function () {
    Route::controller(CustomerController::class)->group(function () {

        Route::get('all-patients', 'index')->name('all-customers');
        Route::get('active-patients', 'active_customer')->name('active-customers');
        Route::get('non-verified-patients', 'non_verified_customers')->name('non-verified-customers');
        Route::get('banned-patients', 'banned_customers')->name('banned-customers');
        Route::get('patient-show/{id}', 'show')->name('customer-show');
        Route::put('patient-info-update/{id}', 'update')->name('customer-info-update');
        Route::put('patient-password-change/{id}', 'password_change')->name('customer-password-change');
        Route::post('send-banned-request/{id}', 'send_banned_request')->name('send-banned-request');
        Route::post('send-verify-request/{id}', 'send_verify_request')->name('send-verify-request');
        Route::post('send-verify-request-to-all', 'send_verify_request_to_all')->name('send-verify-request-to-all');
        Route::post('send-mail-to-patient/{id}', 'send_mail_to_customer')->name('send-mail-to-customer');
        Route::get('send-bulk-mail', 'send_bulk_mail')->name('send-bulk-mail');
        Route::post('send-bulk-mail-to-all', 'send_bulk_mail_to_all')->name('send-bulk-mail-to-all');
        Route::delete('patient-delete/{id}', 'destroy')->name('customer-delete');

    });

});
