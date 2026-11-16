<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\Provider\PartnerSubscriptionController;
use App\Http\Controllers\API\MenuController;
use App\Http\Controllers\API\AllPagesController;
use App\Http\Controllers\API\FrontendController;
use App\Http\Controllers\API\AppointmentController;
use App\Http\Controllers\API\Doctor\LeaveController;
use App\Http\Controllers\API\Patient\MessageController;
use App\Http\Controllers\API\Patient\ProfileController;
use App\Http\Controllers\API\Doctor\DoctorMeetingController;
use App\Http\Controllers\API\Doctor\DoctorWithdrawController;
use App\Http\Controllers\API\Doctor\ZoomCredentialController;
use App\Http\Controllers\API\Patient\AuthenticatedController;
use App\Http\Controllers\API\Doctor\DoctorAppointmentController;
use App\Http\Controllers\API\Doctor\DoctorSocialMediaController;
use App\Http\Controllers\API\Doctor\MessageController as DoctorMessageController;
use App\Http\Controllers\API\Doctor\ProfileController as DoctorProfileController;
use App\Http\Controllers\API\Doctor\AuthenticatedController as DoctorAuthenticatedController;


Broadcast::routes(['middleware' => ['auth:sanctum']]);
Route::post('provider-payments/paystack/webhook', [PartnerSubscriptionController::class, 'paystackWebhook'])->middleware('throttle:120,1')->name('api.provider-payments.paystack.webhook');
Route::prefix('patient')->group(function () {

    Route::middleware(['guest:sanctum'])->group(function () {
        Route::post('register', [AuthenticatedController::class, 'register'])->name('api.patient-register');
        Route::post('login', [AuthenticatedController::class, 'login'])->name('api.patient-login');
        Route::post('forget-password', [AuthenticatedController::class, 'forgetPassword'])->name('api.patient-forget-password');
        Route::post('reset-password', [AuthenticatedController::class, 'resetPassword'])->name('api.patient-reset-password');
    });
    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthenticatedController::class, 'logout'])->name('api.patient-logout');
        Route::post('logout/all-app', [AuthenticatedController::class, 'logoutAllApp'])->name('api.patient-logoutAllApp');

        Route::controller(ProfileController::class)->group(function () {
            Route::get('dashboard', 'dashboard');
            Route::get('profile', 'account');
            Route::post('profile-update', 'updateProfile');
            Route::put('password-update', 'updatePassword');
            Route::get('appointments', 'appointments');
            Route::get('appointments/{id}', 'showAppointment');
            Route::get('orders', 'orders');
            Route::get('orders/{id}', 'showOrder');
            Route::get('meeting-history', 'meetingHistory');
            Route::get('upcoming-meeting', 'upComingMeeting');
        });
        Route::controller(MessageController::class)->group(function () {
            Route::get('appointed-doctors', 'index');
            Route::get('doctor-message/{doctor_id}', 'getMessage');
            Route::post('send-message', 'sendMessage');
            Route::get('seen-message/{doctor_id}', 'seenMessage');
        });
    });
});

Route::prefix('doctor')->group(function () {
    Route::middleware(['guest:doctor_api'])->group(function () {
        Route::post('register', [DoctorAuthenticatedController::class, 'register'])->name('api.doctor-register');
        Route::post('login', [DoctorAuthenticatedController::class, 'login'])->name('api.doctor-login');
        Route::post('forget-password', [DoctorAuthenticatedController::class, 'forgetPassword'])->name('api.doctor-forget-password');
        Route::post('reset-password', [DoctorAuthenticatedController::class, 'resetPassword'])->name('api.doctor-reset-password');
    });
    Route::middleware('auth:doctor_api')->group(function () {
        Route::post('logout', [DoctorAuthenticatedController::class, 'logout'])->name('api.doctor-logout');
        Route::post('logout/all-app', [DoctorAuthenticatedController::class, 'logoutAllApp'])->name('api.doctor-logoutAllApp');

        Route::controller(DoctorProfileController::class)->group(function () {
            Route::get('dashboard', 'dashboard');
            Route::get('profile', 'account');
            Route::post('profile-update', 'updateProfile');
            Route::put('password-update', 'updatePassword');
        });

        Route::controller(DoctorAppointmentController::class)->group(function () {
            Route::get('dosages', 'dosages');
            Route::get('habits', 'habits');
            Route::get('days', 'days');
            Route::get('times', 'times');
            Route::get('medicine-types', 'medicineTypes');
            Route::get('medicines', 'medicines');
            Route::get('today-appointment', 'todayAppointment');
            Route::get('new-appointment', 'newAppointment');
            Route::get('all-appointment', 'allAppointment');
            Route::get('old-appointment/{patient_id}', 'patientOldAppointment');
            Route::post('treatment/{appointment_id}', 'treatment');
            Route::put('update-treatment/{appointment_id}', 'updateTreatment');
            Route::delete('delete-prescribe/{id}', 'deleteAppointmentPrescribe');
            Route::get('prescribes/{appointment_id}', 'prescribes');
            Route::get('payment-history', 'paymentHistory');
            Route::get('schedules', 'schedule');
        });

        Route::controller(DoctorWithdrawController::class)->group(function () {
            Route::get('withdraw-methods', 'withdrawMethods');
            Route::get('withdraw-method-info/{id}', 'getWithDrawMethodInfo');
            Route::get('withdraw-summary', 'withdrawSummary');
            Route::get('withdraw-list', 'withdrawList');
            Route::post('withdraw-request', 'store');
            Route::get('withdraw-request/{id}', 'showWithdraw');
        });
        Route::controller(LeaveController::class)->group(function () {
            Route::get('leave', 'index');
            Route::post('leave', 'store');
            Route::put('leave/{id}', 'update');
            Route::delete('leave/{id}', 'destroy');
        });
        Route::controller(DoctorSocialMediaController::class)->group(function () {
            Route::get('social-link', 'index');
            Route::post('social-link', 'store');
            Route::post('social-link/{id}', 'update');
            Route::put('social-link/status-update/{id}', 'statusUpdate');
            Route::delete('social-link/{id}', 'destroy');
        });

        Route::controller(ZoomCredentialController::class)->group(function () {
            Route::get('zoom-credential', 'index');
            Route::post('zoom-credential', 'store');
        });

        Route::controller(DoctorMeetingController::class)->group(function () {
            Route::get('zoom-meeting', 'index');
            Route::get('meeting-history', 'meetingHistory');
            Route::get('upcoming-meeting', 'upComingMeeting');
            Route::delete('zoom-meeting/{id}', 'destroy');

            Route::get('appointed-patients', 'users');
            Route::post('store-zoom-meeting', 'store');
            Route::get('single-zoom-meeting/{id}', 'edit');
            Route::post('update-zoom-meeting/{id}', 'update');
        });

        Route::controller(DoctorMessageController::class)->group(function () {
            Route::get('appointed-patients', 'index');
            Route::get('patient-message/{patient_id}', 'getMessage');
            Route::post('send-message', 'sendMessage');
            Route::get('seen-message/{patient_id}', 'seenMessage');
        });
    });
});

Route::controller(FrontendController::class)->group(function () {
    Route::get('settings', 'settings');
    Route::get('get-departments', 'departments');
    Route::get('get-locations', 'locations');
    Route::get('get-doctors', 'doctors');
    Route::get('get-services', 'services');
    Route::get('social-links', 'socialLinks');
    Route::get('contact-info', 'contactInfo');
    Route::get('language-list', 'allLanguages');
    Route::get('currency-list', 'allCurrency');
    Route::get('partners', 'partners');
    Route::get('seo-data', 'seoSetting');
    Route::get('subscriber-content', 'subscriberContent');
    Route::get('footer-recent-post', 'footerLatestNews');
    Route::post('contact-us', 'contactUs')->middleware('throttle:3,60');
    Route::post('subscribe-us', 'newsletter_request')->middleware('throttle:3,60');
    Route::get('get-language/{code?}', 'getLanguageFile');
    Route::get('on-boarding-screen', 'boardingScreen');
});
Route::controller(MenuController::class)->group(function () {
    Route::get('main-menu', 'mainMenu');
    Route::get('footer-menu-one', 'footerMenuOne');
    Route::get('footer-menu-two', 'footerMenuTwo');
});
Route::controller(AppointmentController::class)->group(function () {
    Route::get('get-department-doctor/{department_id}', 'getDepartmentDoctor');
    Route::get('get-doctor-schedule', 'getAppointmentSchedule');

    Route::middleware('auth:api')->group(function () {
        Route::post('create-appointment', 'createAppointment');
        Route::get('cart-list', 'totalAppointment');
        Route::delete('cart-remove/{id}', 'removeAppointment');
    });
});
Route::controller(AllPagesController::class)->group(function () {
    Route::get('home', 'index');
    Route::get('about', 'aboutUs');
    Route::get('services', 'service');
    Route::get('services/{slug}', 'serviceDetails');
    Route::get('departments', 'department');
    Route::get('departments/{slug}', 'departmentDetails');
    Route::get('doctors', 'doctors');
    Route::get('doctors/{slug}', 'doctorDetails');
    Route::get('blogs', 'blogs');
    Route::get('blogs/{slug}', 'blogDetails');
    Route::get('category-blogs/{category_slug}', 'blogCategory');
    Route::get('testimonial', 'testimonial');
    Route::get('faq', 'faq');
    Route::get('privacy-policy', 'privacyPolicy');
    Route::get('terms-condition', 'termsCondition');
    Route::get('custom-pages', 'customPages');
});

//print prescription
Route::middleware('payment.api')->group(function () {
    Route::get('patient/print/prescription/{appointment_id}',[ProfileController::class,'printPrescription'] );
    Route::get('doctor/print/prescription/{appointment_id}', [DoctorAppointmentController::class,'printPrescription']);
});
