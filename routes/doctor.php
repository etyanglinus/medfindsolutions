<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Doctor\ProfileController;
use App\Http\Controllers\Doctor\DashboardController;
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
        // Working hours shown on the public profile. Patient appointments, treatments, prescriptions,
        // patient messaging, payouts and Zoom were template features MedFind does not offer; their
        // routes were removed so they cannot be reached.
        Route::get('schedule', [DoctorAppointmentController::class, 'schedule'])->name('schedule');

        Route::get('social-link', [DoctorSocialMediaController::class, 'index'])->name('social-link.index');
        Route::post('social-link', [DoctorSocialMediaController::class, 'store'])->name('social-link.store');
        Route::post('social-link/{id}', [DoctorSocialMediaController::class, 'update'])->name('social-link.update');
        Route::delete('social-link/{id}', [DoctorSocialMediaController::class, 'destroy'])->name('social-link.destroy');
        Route::put('social-link/status-update/{id}', [DoctorSocialMediaController::class, 'statusUpdate'])->name('social-link.status-update');
    });

});
