<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LiveMeetingController;
use App\Http\Controllers\Doctor\ProfileController;
use App\Http\Controllers\Doctor\DashboardController;
use App\Http\Controllers\Doctor\DoctorMeetingController;
use App\Http\Controllers\Doctor\DoctorMessageController;
use App\Http\Controllers\Doctor\DoctorWithdrawController;
use App\Http\Controllers\Doctor\ZoomCredentialController;
use App\Http\Controllers\Doctor\Auth\RegisteredController;
use App\Http\Controllers\Doctor\Auth\NewPasswordController;
use App\Http\Controllers\Doctor\DoctorAppointmentController;
use App\Http\Controllers\Doctor\DoctorSocialMediaController;
use App\Http\Controllers\Doctor\Auth\PasswordResetLinkController;
use App\Http\Controllers\Doctor\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Clinic\Auth\AuthenticatedSessionController as ClinicSessionController;
use App\Http\Controllers\Clinic\Auth\RegisteredController as ClinicRegisteredController;
use App\Http\Controllers\Clinic\DashboardController as ClinicDashboardController;
use App\Http\Controllers\Clinic\ContentController as ClinicContentController;
use App\Http\Controllers\ProviderAdminMessageController;
use App\Http\Controllers\ProviderDocumentController;
use App\Http\Controllers\ProviderInboxController;

Route::get('doctors/login', fn() => to_route('login'));

Route::group(['as' => 'clinic.', 'prefix' => 'clinic', 'middleware' => ['maintenance.mode', 'translation']], function () {
    // ── Guest routes ──────────────────────────────────────────────────────────
    Route::middleware('guest:clinic')->group(function () {
        Route::get('register', [ClinicRegisteredController::class, 'create'])->name('register.form');
        Route::post('register', [ClinicRegisteredController::class, 'store'])->name('register');
        Route::get('complete-registration/{token}', [ClinicRegisteredController::class, 'completeRegistration'])->name('subscription.complete');
        Route::post('login', [ClinicSessionController::class, 'store'])->name('login');
        Route::get('verify/{token}', [ClinicRegisteredController::class, 'verifyEmail'])->name('verification');
    });

    // ── Authenticated routes ──────────────────────────────────────────────────
    Route::middleware('auth:clinic')->group(function () {
        Route::post('logout', [ClinicSessionController::class, 'destroy'])->name('logout');
        Route::post('resend-verification', [ClinicSessionController::class, 'resendVerification'])->name('verification.resend');

        Route::get('dashboard', [ClinicDashboardController::class, 'index'])->name('dashboard');

        Route::get('edit-profile', [ClinicDashboardController::class, 'editProfile'])->name('edit-profile');
        Route::put('profile-update', [ClinicDashboardController::class, 'updateProfile'])->name('profile.update');

        Route::get('change-password', [ClinicDashboardController::class, 'changePassword'])->name('change-password');
        Route::put('change-password', [ClinicDashboardController::class, 'updatePassword'])->name('password.update');

        Route::get('subscription', [ClinicDashboardController::class, 'subscriptionStatus'])->name('subscription.status');
        Route::get('documents', [ProviderDocumentController::class, 'index'])->defaults('type', 'clinic')->name('documents.index');
        Route::post('documents', [ProviderDocumentController::class, 'store'])->defaults('type', 'clinic')->name('documents.store');
        Route::get('inquiries', [ProviderInboxController::class, 'index'])->defaults('type', 'clinic')->name('inquiries.index');
        Route::get('content', [ClinicContentController::class, 'index'])->name('content.index');
        Route::post('gallery', [ClinicContentController::class, 'storeGallery'])->name('gallery.store');
        Route::delete('gallery/{image}', [ClinicContentController::class, 'deleteGallery'])->name('gallery.delete');
        Route::post('branches', [ClinicContentController::class, 'storeBranch'])->name('branches.store');
        Route::delete('branches/{branch}', [ClinicContentController::class, 'deleteBranch'])->name('branches.delete');
        Route::get('admin-messages', [ProviderAdminMessageController::class, 'clinicIndex'])->name('admin-messages.index');
        Route::post('admin-messages', [ProviderAdminMessageController::class, 'clinicStore'])->name('admin-messages.store');
    });
});

Route::group(['as' => 'doctor.', 'prefix' => 'doctor', 'middleware' => ['maintenance.mode', 'translation']], function () {
    Route::post('register', [RegisteredController::class, 'store'])->name('register');
    Route::get('verification/{token}', [RegisteredController::class, 'custom_doctor_verification'])->name('verification');
    Route::get('complete-registration/{token}', [RegisteredController::class, 'completeRegistration'])->name('subscription.complete');
    Route::post('complete-registration/{token}/resend-verification', [RegisteredController::class, 'resendRegistrationVerification'])->middleware('throttle:6,1')->name('registration.verification.resend');

    Route::post('doctor-login', [AuthenticatedSessionController::class, 'store'])->name('login');
    Route::post('forget-password', [PasswordResetLinkController::class, 'custom_forget_password'])->name('forget-password');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'custom_reset_password_page'])->name('password.reset');
    Route::post('/reset-password-store/{token}', [NewPasswordController::class, 'custom_reset_password_store'])->name('password.reset-store');

    Route::middleware(['auth:doctor'])->group(function () {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
        Route::post('resend-verification', [AuthenticatedSessionController::class, 'resendVerification'])->name('verification.resend');

        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('documents', [ProviderDocumentController::class, 'index'])->defaults('type', 'doctor')->name('documents.index');
        Route::post('documents', [ProviderDocumentController::class, 'store'])->defaults('type', 'doctor')->name('documents.store');
        Route::get('inquiries', [ProviderInboxController::class, 'index'])->defaults('type', 'doctor')->name('inquiries.index');
        Route::get('admin-messages', [ProviderAdminMessageController::class, 'doctorIndex'])->name('admin-messages.index');
        Route::post('admin-messages', [ProviderAdminMessageController::class, 'doctorStore'])->name('admin-messages.store');

        Route::controller(ProfileController::class)->group(function () {
            Route::get('edit-profile', 'edit_profile')->name('edit-profile');
            Route::put('profile-update', 'profile_update')->name('profile-update');
            Route::get('update-password', 'change_password')->name('change-password');
            Route::put('update-password', 'update_password')->name('update-password');
        });
        Route::controller(DoctorAppointmentController::class)->group(function () {
            Route::get('today-appointment', 'todayAppointment')->name('today.appointment');
            Route::get('new-appointment', 'newAppointment')->name('new.appointment');
            Route::get('all-appointment', 'allAppointment')->name('all.appointment');
            Route::get('not-treated-appointment', 'notTreatedAppointments')->name('not.treated.appointment');
            Route::get('old-appointment/{id}', 'oldAppointment')->name('old.appointment');
            Route::get('treatment/{id}', 'treatment')->name('treatment');
            Route::post('treatment-store/{id}', 'storeTreatment')->name('treatment.store');
            Route::get('treatment-edit/{id}', 'editTreatment')->name('treatment.edit');
            Route::post('treatment-update/{id}', 'updateTreatment')->name('treatment.update');
            Route::get('delete-appointment-prescribe/{id}', 'deleteAppointmentPrescribe')->name('delete.appointment.prescribe');
            Route::get('already-treatment/{id}', 'allReadyTreatment')->name('already.treatment');
            Route::get('prescription/{id}', 'printPrescription')->name('print.prescription');
            Route::get('payment-history', 'paymentHistory')->name('payment.history');
            Route::get('schedule', 'schedule')->name('schedule');
        });
        Route::controller(DoctorMessageController::class)->group(function () {
            Route::get('message', 'index')->name('message.index');
            Route::get('message-box/{id}', 'messagebox')->name('message.box');
            Route::get('get-message/{id}', 'getmessage')->name('get.message');
            Route::get('seen-message/{id}', 'seenMessage')->name('seen.message');
            Route::post('send-message', 'sendmessage')->name('send.message');
        });
        Route::resource('withdraw', DoctorWithdrawController::class)->only(['index', 'create', 'store', 'show']);
        Route::get('get-withdraw-account-info/{id}', [DoctorWithdrawController::class, 'getWithDrawAccountInfo'])->name('get-withdraw-account-info');

        Route::controller(ZoomCredentialController::class)->group(function () {
            Route::get('zoom-credential', 'index')->name('zoom-credential');
            Route::post('zoom-credential', 'store')->name('zoom-credential.store');
        });

        Route::controller(DoctorMeetingController::class)->group(function () {
            Route::get('/zoom-meetings', 'index')->name('zoom-meetings');
            Route::get('/create-zoom-meeting', 'create')->name('create-zoom-meeting');
            Route::post('/store-zoom-meeting', 'store')->name('store-zoom-meeting');
            Route::get('/edit-zoom-meeting/{id}', 'edit')->name('edit-zoom-meeting');
            Route::post('/update-zoom-meeting/{id}', 'update')->name('update-zoom-meeting');
            Route::delete('/delete-zoom-meeting/{id}', 'destroy')->name('delete-zoom-meeting');
            
            Route::get('/meeting-history', 'meetingHistory')->name('meeting-history');
            Route::get('/upcomming-meeting', 'upCommingMeeting')->name('upcomming-meeting');

        });

        Route::get('social-link', [DoctorSocialMediaController::class, 'index'])->name('social-link.index');
        Route::post('social-link', [DoctorSocialMediaController::class, 'store'])->name('social-link.store');
        Route::post('social-link/{id}', [DoctorSocialMediaController::class, 'update'])->name('social-link.update');
        Route::delete('social-link/{id}', [DoctorSocialMediaController::class, 'destroy'])->name('social-link.destroy');
        Route::put('social-link/status-update/{id}', [DoctorSocialMediaController::class, 'statusUpdate'])->name('social-link.status-update');
    });

});
