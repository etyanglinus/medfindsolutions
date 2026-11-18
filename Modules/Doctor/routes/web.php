<?php

use Illuminate\Support\Facades\Route;
use Modules\Doctor\app\Http\Controllers\LeaveController;
use Modules\Doctor\app\Http\Controllers\DoctorController;
use Modules\Doctor\app\Http\Controllers\FacilityController;
use Modules\Doctor\app\Http\Controllers\LocationController;
use Modules\Doctor\app\Http\Controllers\DepartmentController;
use Modules\Doctor\app\Http\Controllers\DepartmentFaqController;
use Modules\Doctor\app\Http\Controllers\DepartmentUtilityController;
use Modules\Doctor\app\Http\Controllers\InsuranceProviderController;
use Modules\Doctor\app\Http\Controllers\LicensingBodyController;
use Modules\Doctor\app\Http\Controllers\SubscriptionController;
use Modules\Doctor\app\Http\Controllers\SubscriptionPackageController;
use Modules\Doctor\app\Http\Controllers\ClinicAdminController;
use App\Http\Controllers\Admin\ProviderMessageController;
use App\Http\Controllers\ProviderDocumentController;

Route::group(['as' => 'admin.', 'prefix' => 'admin', 'middleware' => ['auth:admin', 'translation']], function () {
    Route::resource('department', DepartmentController::class)->names('department')->except('show');
    Route::put('/department/status-update/{id}', [DepartmentController::class, 'statusUpdate'])->name('department.status-update');

    Route::controller(DepartmentUtilityController::class)->group(function () {
        Route::get('department-gallery/{id}', 'showGallery')->name('department.gallery');
        Route::put('department-gallery/{id}', 'updateGallery')->name('department.gallery.update');
        Route::delete('department-gallery/{id}', 'deleteGallery')->name('department.gallery.delete');

        Route::get('department-videos/{id}', 'showVideos')->name('department.videos');
        Route::put('department-videos/{id}', 'updateVideos')->name('department.videos.update');
        Route::delete('department-videos/{id}', 'deleteVideos')->name('department.videos.delete');
    });

    Route::get('faq-by-department/{slug}', [DepartmentFaqController::class, 'index'])->name('faq.by.department');
    Route::resource('department-faq', DepartmentFaqController::class)->names('department-faq')->only(['store', 'update', 'destroy']);
    Route::put('department-faq/status-update/{id}', [DepartmentFaqController::class, 'statusUpdate'])->name('department-faq.status-update');

    Route::get('location', [LocationController::class, 'index'])->name('location.index');
    Route::post('location', [LocationController::class, 'store'])->name('location.store');
    Route::post('location/{id}', [LocationController::class, 'update'])->name('location.update');
    Route::delete('location/{id}', [LocationController::class, 'destroy'])->name('location.destroy');
    Route::put('/location/status-update/{id}', [LocationController::class, 'statusUpdate'])->name('location.status-update');

    Route::get('licensing-body', [LicensingBodyController::class, 'index'])->name('licensingbody.index');
    Route::post('licensing-body', [LicensingBodyController::class, 'store'])->name('licensingbody.store');
    Route::post('licensing-body/{id}', [LicensingBodyController::class, 'update'])->name('licensingbody.update');
    Route::delete('licensing-body/{id}', [LicensingBodyController::class, 'destroy'])->name('licensingbody.destroy');
    Route::put('/licensing-body/status-update/{id}', [LicensingBodyController::class, 'statusUpdate'])->name('licensingbody.status-update');

    Route::get('insurance-provider', [InsuranceProviderController::class, 'index'])->name('insuranceprovider.index');
    Route::post('insurance-provider', [InsuranceProviderController::class, 'store'])->name('insuranceprovider.store');
    Route::post('insurance-provider/{id}', [InsuranceProviderController::class, 'update'])->name('insuranceprovider.update');
    Route::delete('insurance-provider/{id}', [InsuranceProviderController::class, 'destroy'])->name('insuranceprovider.destroy');
    Route::put('/insurance-provider/status-update/{id}', [InsuranceProviderController::class, 'statusUpdate'])->name('insuranceprovider.status-update');

    Route::get('service', [\Modules\Doctor\app\Http\Controllers\ServiceController::class, 'index'])->name('service.index');
    Route::post('service', [\Modules\Doctor\app\Http\Controllers\ServiceController::class, 'store'])->name('service.store');
    Route::post('service/{id}', [\Modules\Doctor\app\Http\Controllers\ServiceController::class, 'update'])->name('service.update');
    Route::delete('service/{id}', [\Modules\Doctor\app\Http\Controllers\ServiceController::class, 'destroy'])->name('service.destroy');
    Route::put('/service/status-update/{id}', [\Modules\Doctor\app\Http\Controllers\ServiceController::class, 'statusUpdate'])->name('service.status-update');

    Route::resource('doctor', DoctorController::class)->names('doctor');
    Route::put('/doctor/status-update/{id}', [DoctorController::class, 'statusUpdate'])->name('doctor.status-update');
    Route::post('doctor-send-verify-mail/{id}', [DoctorController::class, 'send_verify_request'])->name('doctor-send-verify-mail');
    Route::post('doctor-send-verify-mail-to-all', [DoctorController::class, 'send_verify_request_to_all'])->name('doctor-send-verify-mail-to-all');

    Route::resource('facility', FacilityController::class)->names('facility');
    Route::put('/facility/status-update/{id}', [FacilityController::class, 'statusUpdate'])->name('facility.status-update');
    Route::resource('clinic-listings', ClinicAdminController::class)->only(['index', 'edit', 'update', 'destroy'])->names('clinic-listing');
    Route::put('listing-completeness/{type}/{id}', \App\Http\Controllers\Admin\IncompleteListingController::class)
        ->whereIn('type', ['doctor', 'clinic'])->whereNumber('id')->name('listing-completeness.update');
    Route::get('provider-messages', [ProviderMessageController::class, 'index'])->name('provider-messages.index');
    Route::post('provider-messages', [ProviderMessageController::class, 'store'])->name('provider-messages.store');
    Route::get('provider-documents', [ProviderDocumentController::class, 'adminIndex'])->name('provider-documents.index');
    Route::get('provider-documents/{document}/download', [ProviderDocumentController::class, 'adminDownload'])->name('provider-documents.download');
    Route::put('provider-documents/{document}', [ProviderDocumentController::class, 'adminUpdate'])->name('provider-documents.update');

    Route::get('leave', [LeaveController::class, 'index'])->name('leave.index');
    Route::get('leave/{id}', [LeaveController::class, 'show'])->name('leave.show');
    Route::delete('leave/{id}', [LeaveController::class, 'destroy'])->name('leave.destroy');
    Route::put('leave/status-update/{id}', [LeaveController::class, 'statusUpdate'])->name('leave.status-update');

    // ── Subscription management ─────────────────────────────────────────────
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::resource('packages', SubscriptionPackageController::class)->except('show');
        Route::get('payment-details', [SubscriptionPackageController::class, 'paymentDetails'])->name('payment-details');
        Route::put('payment-details', [SubscriptionPackageController::class, 'updatePaymentDetails'])->name('payment-details.update');
        // Doctor subscriptions
        Route::get('doctors', [SubscriptionController::class, 'doctorIndex'])->name('doctor.index');
        Route::get('doctors/{id}', [SubscriptionController::class, 'doctorShow'])->name('doctor.show');
        Route::put('doctors/{id}/approve', [SubscriptionController::class, 'doctorApprove'])->name('doctor.approve');
        Route::put('doctors/{id}/reject', [SubscriptionController::class, 'doctorReject'])->name('doctor.reject');

        // Clinic subscriptions
        Route::get('clinics', [SubscriptionController::class, 'clinicIndex'])->name('clinic.index');
        Route::get('clinics/{id}', [SubscriptionController::class, 'clinicShow'])->name('clinic.show');
        Route::put('clinics/{id}/approve', [SubscriptionController::class, 'clinicApprove'])->name('clinic.approve');
        Route::put('clinics/{id}/reject', [SubscriptionController::class, 'clinicReject'])->name('clinic.reject');
    });
});
