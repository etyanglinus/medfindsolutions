<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Patient\AppointmentController;
use App\Http\Controllers\Patient\BlogController;
use App\Http\Controllers\ProviderEngagementController;
use App\Http\Controllers\Provider\PartnerAuthController;
use App\Http\Controllers\Provider\PartnerSubscriptionController;
use App\Http\Controllers\Provider\PartnerPortalController;
use App\Http\Controllers\SchoolDirectoryController;
use App\Http\Controllers\SupplierDirectoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['translation', 'maintenance.mode'])->group(function () {
    Route::post('subscription/{type}/quote', \App\Http\Controllers\SubscriptionQuoteController::class)
        ->whereIn('type', ['doctor', 'clinic', 'school', 'supplier'])->middleware('throttle:30,1')->name('subscription.quote');
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('home', fn() => to_route('home'));

    Route::name('website.')->group(function () {
        Route::view('contact-us', 'patient.contact-us');
        Route::get('partner/{type}/register', [PartnerAuthController::class, 'registerForm'])->whereIn('type', ['school', 'supplier'])->name('partner.register');
        Route::post('partner/{type}/register', [PartnerAuthController::class, 'register'])->whereIn('type', ['school', 'supplier'])->name('partner.register.store');
        Route::get('partner/{type}/login', [PartnerAuthController::class, 'loginForm'])->whereIn('type', ['school', 'supplier'])->name('partner.login');
        Route::post('partner/{type}/login', [PartnerAuthController::class, 'login'])->whereIn('type', ['school', 'supplier'])->name('partner.login.store');
        Route::post('partner/{type}/resend-verification', [PartnerAuthController::class, 'resendVerification'])->middleware('throttle:6,1')->whereIn('type', ['school', 'supplier'])->name('partner.verification.resend');
        Route::get('partner/{type}/verify-email/{token}', [PartnerAuthController::class, 'verifyEmail'])->middleware('signed')->whereIn('type', ['school', 'supplier'])->name('partner.verification.verify');
        Route::post('partner/{type}/logout', [PartnerAuthController::class, 'logout'])->whereIn('type', ['school', 'supplier'])->name('partner.logout');
        Route::get('partner/{type}/dashboard', [PartnerPortalController::class, 'dashboard'])->whereIn('type', ['school', 'supplier'])->name('partner.dashboard');
        Route::get('partner/{type}/profile', [PartnerPortalController::class, 'profile'])->whereIn('type', ['school', 'supplier'])->name('partner.profile');
        Route::put('partner/{type}/profile', [PartnerPortalController::class, 'updateProfile'])->whereIn('type', ['school', 'supplier'])->name('partner.profile.update');
        Route::get('partner/{type}/account', [PartnerPortalController::class, 'account'])->whereIn('type', ['school', 'supplier'])->name('partner.account');
        Route::put('partner/{type}/account', [PartnerPortalController::class, 'updateAccount'])->whereIn('type', ['school', 'supplier'])->name('partner.account.update');
        Route::post('partner/{type}/courses', [PartnerPortalController::class, 'saveCourse'])->whereIn('type', ['school', 'supplier'])->name('partner.courses.store');
        Route::put('partner/{type}/courses/{course}', [PartnerPortalController::class, 'saveCourse'])->whereIn('type', ['school', 'supplier'])->name('partner.courses.update');
        Route::delete('partner/{type}/courses/{course}', [PartnerPortalController::class, 'deleteCourse'])->whereIn('type', ['school', 'supplier'])->name('partner.courses.delete');
        Route::post('partner/{type}/products', [PartnerPortalController::class, 'saveProduct'])->whereIn('type', ['school', 'supplier'])->name('partner.products.store');
        Route::put('partner/{type}/products/{product}', [PartnerPortalController::class, 'saveProduct'])->whereIn('type', ['school', 'supplier'])->name('partner.products.update');
        Route::delete('partner/{type}/products/{product}', [PartnerPortalController::class, 'deleteProduct'])->whereIn('type', ['school', 'supplier'])->name('partner.products.delete');
        Route::get('partner/{type}/products/{product}/catalogue', [PartnerPortalController::class, 'downloadCatalogue'])->whereIn('type', ['school', 'supplier'])->name('partner.products.catalogue');
        Route::get('partner/{type}/quotations', [PartnerPortalController::class, 'quotations'])->whereIn('type', ['school', 'supplier'])->name('partner.quotations');
        Route::patch('partner/{type}/quotations/{quote}', [PartnerPortalController::class, 'updateQuotation'])->whereIn('type', ['school', 'supplier'])->name('partner.quotations.update');
        Route::patch('partner/{type}/enquiries/{enquiry}', [PartnerPortalController::class, 'updateEnquiry'])->whereIn('type', ['school', 'supplier'])->name('partner.enquiries.update');
        Route::get('partner/{type}/admin-messages', [\App\Http\Controllers\ProviderAdminMessageController::class, 'partnerIndex'])->whereIn('type', ['school', 'supplier'])->name('partner.admin-messages.index');
        Route::post('partner/{type}/admin-messages', [\App\Http\Controllers\ProviderAdminMessageController::class, 'partnerStore'])->whereIn('type', ['school', 'supplier'])->name('partner.admin-messages.store');
        Route::get('partner/{type}/documents', [\App\Http\Controllers\ProviderDocumentController::class, 'partnerIndex'])->whereIn('type', ['school', 'supplier'])->name('partner.documents.index');
        Route::post('partner/{type}/documents', [\App\Http\Controllers\ProviderDocumentController::class, 'partnerStore'])->whereIn('type', ['school', 'supplier'])->name('partner.documents.store');
        Route::get('partner/{type}/subscription/{token}', [PartnerSubscriptionController::class, 'show'])->whereIn('type', ['school', 'supplier'])->name('partner.subscription');
        Route::get('partner/{type}/subscription', [PartnerSubscriptionController::class, 'renewal'])->middleware('auth:school,supplier')->whereIn('type', ['school', 'supplier'])->name('partner.subscription.renew');
        Route::get('healthcare-courses', [SchoolDirectoryController::class, 'index'])->name('healthcare-courses');
        Route::get('medical-suppliers', [SupplierDirectoryController::class, 'index'])->name('medical-suppliers');
        Route::post('medical-suppliers/{slug}/quotations', [SupplierDirectoryController::class, 'quotation'])->middleware('throttle:10,1')->name('medical-supplier.quotations');
        Route::get('medical-suppliers/{slug}/products/{productSlug}', [SupplierDirectoryController::class, 'product'])->name('medical-supplier.product');
        Route::get('medical-suppliers/{slug}/products/{product}/catalogue', [SupplierDirectoryController::class, 'catalogue'])->name('medical-supplier.catalogue');
        Route::get('healthcare-courses/{slug}', [SchoolDirectoryController::class, 'course'])->name('healthcare-course');
        Route::get('healthcare-schools/{slug}', [SchoolDirectoryController::class, 'institution'])->name('healthcare-institution');
        Route::post('healthcare-schools/{slug}/enquiries', [SchoolDirectoryController::class, 'enquire'])->middleware('throttle:10,1')->name('healthcare-institution.enquiries');
        Route::get('medical-suppliers/{slug}', [SupplierDirectoryController::class, 'supplier'])->name('medical-supplier');
        Route::controller(HomeController::class)->group(function () {
            Route::get('/about-us', 'aboutUs');
            Route::get('/service', 'service')->name('services');
            Route::get('service-details/{slug}', 'serviceDetails')->name('service.details');
            Route::get('/department', 'department')->name('departments');
            Route::get('/department-details/{slug}', 'departmentDetails')->name('department.details');
            Route::get('doctors', 'doctor')->name('doctors');
            Route::get('doctor-details/{slug}', 'doctorDetails')->name('doctor.details');
            Route::get('/search-doctor', 'searchDoctor')->name('search.doctor');
            Route::get('facilities', 'facilities')->name('facilities');
            Route::get('facility-details/{slug}', 'facilityDetails')->name('facility.details');
            Route::get('/testimonial', 'testimonial')->name('testimonial');
            Route::get('/faq', 'faq')->name('faq');
            Route::get('privacy-policy', 'privacyPolicy')->name('privacy-policy');
            Route::get('terms-condition', 'termsCondition')->name('termsCondition');
        });
        Route::controller(BlogController::class)->group(function () {
            Route::get('/blog', 'blog')->name('blogs');
            Route::get('/blog-details/{slug}', 'blogDetails')->name('blog.details');
            Route::get('/blog-category/{slug}', 'blogCategory')->name('blog.category');
            Route::post('comment-store/{slug}', 'commentStore')->name('comment.store');
        });

        Route::controller(AppointmentController::class)->group(function () {
            // ajax request for appointment
            Route::get('get-appointment/', 'getAppointment');
            Route::get('get-department-doctor/{id}', 'getDepartmentDoctor');

            //appointment add to cart
            Route::post('create-appointment', 'createAppointment')->name('create.appointment');
            Route::get('remove-appointment/{id}', 'removeAppointment');
        });

        Route::post('{type}/{provider}/inquiries', [ProviderEngagementController::class, 'storeInquiry'])->whereIn('type', ['doctor', 'clinic'])->name('provider.inquiries.store');
        Route::post('{type}/{provider}/reviews', [ProviderEngagementController::class, 'storeReview'])->whereIn('type', ['doctor', 'clinic'])->name('provider.reviews.store');

        Route::get('page/{slug}', [HomeController::class, 'customPage'])->name('page');
    });
});
