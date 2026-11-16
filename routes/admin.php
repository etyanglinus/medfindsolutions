<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\RolesController;
/*  Start Admin panel Controller  */
use App\Http\Controllers\Admin\FaqPageController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AboutUsPageController;
use App\Http\Controllers\Admin\Auth\NewPasswordController;
use App\Http\Controllers\Admin\Auth\PasswordResetLinkController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\ProviderProfileController;
use App\Http\Controllers\Admin\SubscriptionCouponController;
use App\Http\Controllers\ProviderDocumentController;
use App\Http\Controllers\Admin\ProviderPaymentReviewController;
use App\Http\Controllers\Admin\SupplierListingReviewController;
use App\Http\Controllers\Admin\SupplierCategoryController;

/*  End Admin panel Controller  */

Route::group(['middleware'=>'translation', 'as' => 'admin.', 'prefix' => 'admin'], function () {
    /* Start admin auth route */
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('store-login', [AuthenticatedSessionController::class, 'store'])->name('store-login');
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forget-password', [PasswordResetLinkController::class, 'custom_forget_password'])->name('forget-password');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'custom_reset_password_page'])->name('password.reset');
    Route::post('/reset-password-store/{token}', [NewPasswordController::class, 'custom_reset_password_store'])->name('password.reset-store');
    /* End admin auth route */
    
    Route::middleware(['auth:admin'])->group(function () {
        Route::get('employees/invite', [\App\Http\Controllers\Admin\EmployeeInvitationController::class, 'index'])->name('employees.invite');
        Route::post('employees/invite', [\App\Http\Controllers\Admin\EmployeeInvitationController::class, 'store'])->name('employees.invite.store');
        Route::post('employees/{employee}/resend-invitation', [\App\Http\Controllers\Admin\EmployeeInvitationController::class, 'resend'])->name('employees.invite.resend');
        Route::get('employee-password', [\App\Http\Controllers\Admin\EmployeeInvitationController::class, 'editPassword'])->name('employee-password.edit');
        Route::put('employee-password', [\App\Http\Controllers\Admin\EmployeeInvitationController::class, 'updatePassword'])->name('employee-password.update');
        Route::get('reports/account-creation', [\App\Http\Controllers\Admin\AccountReportController::class, 'index'])->name('reports.account-creation');
        Route::get('clients/create', [\App\Http\Controllers\Admin\ClientOnboardingController::class, 'create'])->name('clients.create');
        Route::post('clients', [\App\Http\Controllers\Admin\ClientOnboardingController::class, 'store'])->name('clients.store');
        Route::resource('admin', AdminController::class)->except('show');
        Route::put('admin-status/{id}', [AdminController::class, 'changeStatus'])->name('admin.status');
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
        Route::get('/', [DashboardController::class, 'dashboard'])->name('home');
        Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

        // ── Mail test ────────────────────────────────────────────────────────
        Route::get('test-mail',  [SettingController::class, 'testMailForm'])->name('test-mail');
        Route::post('test-mail', [SettingController::class, 'sendTestMail'])->name('test-mail.send');
        Route::get('provider-profiles', [ProviderProfileController::class, 'index'])->name('provider-profiles.index');
        Route::delete('provider-profiles/{profile}', [ProviderProfileController::class, 'destroy'])->name('provider-profiles.destroy');
        Route::post('provider-profiles/{profile}/approve', [ProviderProfileController::class, 'approve'])->name('provider-profiles.approve');
        Route::post('provider-profiles/{profile}/verify', [ProviderProfileController::class, 'verify'])->name('provider-profiles.verify');
        Route::post('provider-profiles/{profile}/publish', [ProviderProfileController::class, 'publish'])->name('provider-profiles.publish');
        Route::post('provider-profiles/{profile}/changes', [ProviderProfileController::class, 'requestChanges'])->name('provider-profiles.changes');
        Route::post('provider-profiles/{profile}/suspend', [ProviderProfileController::class, 'suspend'])->name('provider-profiles.suspend');
        Route::get('provider-payments', [ProviderPaymentReviewController::class, 'index'])->name('provider-payments.index');
        Route::get('provider-payments/{transaction}/receipt', [\App\Http\Controllers\ProviderReceiptController::class, 'admin'])->name('provider-payments.receipt');
        Route::post('provider-payments/{transaction}/checkout', [\App\Http\Controllers\Provider\PaystackCheckoutController::class, 'retry'])->name('provider-payments.checkout');
        Route::post('provider-payments/{transaction}/review', [ProviderPaymentReviewController::class, 'update'])->name('provider-payments.update');
        Route::post('provider-payments/{transaction}/recheck', [ProviderPaymentReviewController::class, 'recheck'])->middleware('throttle:20,1')->name('provider-payments.recheck');
        Route::get('supplier-categories', [SupplierCategoryController::class, 'index'])->name('supplier-categories.index');
        Route::post('supplier-categories', [SupplierCategoryController::class, 'store'])->name('supplier-categories.store');
        Route::put('supplier-categories/{category}', [SupplierCategoryController::class, 'update'])->name('supplier-categories.update');
        Route::get('supplier-listings', [SupplierListingReviewController::class, 'index'])->name('supplier-listings.index');
        Route::put('listing-verification', [\App\Http\Controllers\Admin\ListingVerificationController::class, 'update'])->name('listing-verification.update');
        Route::post('maintenance/start', [\App\Http\Controllers\Admin\MaintenanceController::class, 'start'])->name('maintenance.start');
        Route::post('maintenance/stop', [\App\Http\Controllers\Admin\MaintenanceController::class, 'stop'])->name('maintenance.stop');
        Route::get('school-courses', [\App\Http\Controllers\Admin\SchoolCourseReviewController::class, 'index'])->name('school-courses.index');
        Route::put('school-courses/{course}/review', [\App\Http\Controllers\Admin\SchoolCourseReviewController::class, 'update'])->name('school-courses.review');
        Route::put('supplier-products/{product}/review', [SupplierListingReviewController::class, 'updateProduct'])->name('supplier-products.review');
        Route::put('supplier-listings/{supplier}/feature', [SupplierListingReviewController::class, 'featureSupplier'])->name('supplier-listings.feature');
        Route::put('supplier-listings/{supplier}/banner', [SupplierListingReviewController::class, 'reviewBanner'])->name('supplier-listings.banner');
        Route::get('subscription-coupons', [SubscriptionCouponController::class, 'index'])->name('subscription-coupons.index');
        Route::post('subscription-coupons', [SubscriptionCouponController::class, 'store'])->name('subscription-coupons.store');
        Route::put('subscription-coupons/{coupon}', [SubscriptionCouponController::class, 'update'])->name('subscription-coupons.update');
        Route::delete('subscription-coupons/{coupon}', [SubscriptionCouponController::class, 'destroy'])->name('subscription-coupons.destroy');

        Route::controller(AdminProfileController::class)->group(function () {
            Route::get('edit-profile', 'edit_profile')->name('edit-profile');
            Route::put('profile-update', 'profile_update')->name('profile-update');
            Route::put('update-password', 'update_password')->name('update-password');
        });

        Route::get('role/assign', [RolesController::class, 'assignRoleView'])->name('role.assign');
        Route::post('role/assign/{id}', [RolesController::class, 'getAdminRoles'])->name('role.assign.admin');
        Route::put('role/assign', [RolesController::class, 'assignRoleUpdate'])->name('role.assign.update');
        Route::resource('/role', RolesController::class);
    });
    // Settings routes
    Route::get('settings', [SettingController::class, 'settings'])->name('settings');

    Route::name('pages.')->prefix('pages')->group(function () {
        Route::get('about-us', [AboutUsPageController::class, 'index'])->name('about-us.index');
        Route::put('about-us', [AboutUsPageController::class, 'update'])->name('about-us.update');
        Route::get('faq', [FaqPageController::class, 'index'])->name('faq.index');
        Route::put('faq', [FaqPageController::class, 'update'])->name('faq.update');
    });
    Route::get('sync-modules', [App\Http\Controllers\Admin\AddonsController::class, 'syncModules'])->name('addons.sync');
});
