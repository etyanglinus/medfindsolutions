<?php

namespace Tests\Feature;

use App\Exceptions\AccessPermissionDeniedException;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProviderProfileController;
use App\Models\Admin;
use App\Models\AccountCreationAudit;
use App\Models\ProviderProfile;
use App\Models\ProviderPaymentTransaction;
use App\Models\ProviderSubscription;
use App\Services\AdminDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Modules\Doctor\app\Models\Clinic;
use Modules\Doctor\app\Models\ClinicSubscription;
use Modules\Doctor\app\Http\Controllers\ClinicAdminController;
use Modules\HealthcareInstitution\app\Models\HealthcareInstitution;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;
use Tests\TestCase;

class AdminDashboardOperationsTest extends TestCase
{
    use RefreshDatabase;

    private function actor(bool $owner = true): Admin
    {
        $admin = new Admin;
        $admin->forceFill(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => bcrypt('password'), 'status' => 'active', 'is_super_admin' => $owner])->save();
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    private function client(string $type): array
    {
        $model = match ($type) { 'clinic' => Clinic::class, 'school' => HealthcareInstitution::class, 'supplier' => MedicalSupplier::class };
        $client = $model::create(['name' => $type.' account', 'slug' => $type.'-account', $type === 'clinic' ? 'email' : 'official_email' => $type.'@example.test', 'password' => bcrypt('password'), 'phone' => '0700000000', 'email_verified_at' => now(), 'status' => 1]);
        $profile = ProviderProfile::create(['provider_type' => $type, 'provider_id' => $client->id, 'status' => 'published', 'published_at' => now()]);
        if ($type !== 'clinic') $client->update(['provider_profile_id' => $profile->id]);
        $subscription = ProviderSubscription::create(['provider_profile_id' => $profile->id, 'package_name' => 'Premium', 'plan_code' => 'premium', 'amount' => 1200, 'currency' => 'KES', 'duration_months' => 3, 'status' => 'approved', 'approved_at' => now(), 'starts_at' => now()->subDay(), 'expires_at' => now()->addDays(20)]);
        $payment = ProviderPaymentTransaction::create(['provider_profile_id' => $profile->id, 'provider_subscription_id' => $subscription->id, 'gateway' => 'bank_transfer', 'gateway_reference' => 'REF-'.$type, 'idempotency_key' => 'key-'.$type, 'amount' => 1200, 'currency' => 'KES', 'status' => 'paid', 'paid_at' => now(), 'gateway_payload' => ['confirmed_by' => auth('admin')->id()]]);
        return [$client, $profile, $subscription, $payment];
    }

    public function test_dashboard_deduplicates_mirrored_revenue_and_active_accounts(): void
    {
        $this->actor();
        [$clinic] = $this->client('clinic');
        foreach (['REF-clinic' => 1200, 'INDEPENDENT' => 300] as $reference => $amount) ClinicSubscription::create(['clinic_id' => $clinic->id, 'package_name' => 'Premium', 'package_price' => $amount, 'plan_duration' => 3, 'status' => 'approved', 'transaction_code' => $reference, 'approved_at' => now()->subDay(), 'expires_at' => now()->addDays(20)]);
        $data = app(AdminDashboardService::class)->data(Request::create('/admin/dashboard'));
        $this->assertEquals(1500, $data['totalRevenue']);
        $this->assertSame(1, $data['activeCount']);
        $this->assertSame(1, $data['registrationCount']);
        $this->assertSame(1, $data['staff'][0]['confirmed']);
        $this->assertSame(1, $data['staff'][0]['created']);
        ProviderPaymentTransaction::where('gateway_reference', 'REF-clinic')->firstOrFail()->update(['status' => 'refunded']);
        $data = app(AdminDashboardService::class)->data(Request::create('/admin/dashboard'));
        $this->assertEquals(300, $data['totalRevenue']);
    }

    public function test_dashboard_never_exposes_unassigned_types_finances_or_creator_details(): void
    {
        $this->actor(false)->givePermissionTo('school.view');
        $this->client('school'); $this->client('supplier');
        $data = app(AdminDashboardService::class)->data(Request::create('/admin/dashboard'));
        $this->assertCount(1, $data['providerCards']);
        $this->assertSame('school', $data['providerCards'][0]['type']);
        $this->assertFalse($data['canFinance']);
        $this->assertFalse($data['canCreators']);
        $this->assertSame([], $data['revenue']);
        $this->assertSame([], $data['staff']);
        $this->assertSame([], $data['sources']);
        $this->assertCount(1, $data['recent']);
    }

    public function test_date_filter_excludes_old_payments_and_future_subscriptions_from_active_count(): void
    {
        $this->actor();
        [, , $subscription, $payment] = $this->client('supplier');
        $payment->update(['paid_at' => now()->subMonths(2)]);
        $subscription->update(['starts_at' => now()->addMonth(), 'expires_at' => now()->addMonths(4)]);
        $data = app(AdminDashboardService::class)->data(Request::create('/admin/dashboard', 'GET', ['period' => '7']));
        $this->assertEquals(0, $data['totalRevenue']);
        $this->assertSame(0, $data['activeCount']);
        $this->assertCount(7, $data['chartLabels']);
    }

    public function test_deleting_school_supplier_and_facility_revokes_access_but_keeps_history(): void
    {
        $this->actor();
        foreach (['school', 'supplier', 'clinic'] as $type) {
            [$client, $profile, $subscription, $payment] = $this->client($type);
            if ($type === 'clinic') app(ClinicAdminController::class)->destroy($client);
            else app(ProviderProfileController::class)->destroy($profile);
            $this->assertSoftDeleted($client);
            $this->assertSoftDeleted($profile);
            $this->assertSame('cancelled', $subscription->fresh()->status);
            $this->assertSame('paid', $payment->fresh()->status);
            $guard = $type === 'clinic' ? 'clinic' : $type;
            $this->assertNull(auth($guard)->getProvider()->retrieveById($client->id));
            $this->assertDatabaseHas('account_creation_audits', ['provider_type' => $type, 'provider_id' => $client->id]);
            $this->assertNotNull($payment->fresh()->providerProfile->provider);
        }
        $data = app(AdminDashboardService::class)->data(Request::create('/admin/dashboard'));
        $this->assertEquals(3600, $data['totalRevenue']);
        $this->assertSame(0, $data['activeCount']);
        $this->assertSame(0, collect($data['providerCards'])->sum('count'));
    }

    public function test_employee_without_delete_permission_cannot_delete_a_supplier(): void
    {
        $this->actor(false)->givePermissionTo('supplier.view');
        [, $profile] = $this->client('supplier');
        $this->expectException(AccessPermissionDeniedException::class);
        app(ProviderProfileController::class)->destroy($profile);
    }

    public function test_deleted_supplier_cannot_be_activated_by_a_delayed_job_and_products_are_unpublished(): void
    {
        $this->actor();
        [$client, $profile, $subscription, $payment] = $this->client('supplier');
        $subscription->update(['metadata' => ['admin_onboarding' => true, 'login_secret' => 'encrypted-placeholder']]);
        $product = \Modules\MedicalSupplier\app\Models\SupplierProduct::create(['medical_supplier_id' => $client->id, 'name' => 'Product', 'slug' => 'product', 'is_published' => true, 'approval_status' => 'approved']);
        \Illuminate\Support\Facades\Mail::fake();
        app(ProviderProfileController::class)->destroy($profile);
        (new \App\Jobs\ActivateAdminClient($payment->id))->handle();
        $this->assertFalse($product->fresh()->is_published);
        $this->assertArrayNotHasKey('login_secret', $subscription->fresh()->metadata);
        $this->assertSame('cancelled', $subscription->fresh()->status);
        $this->assertSoftDeleted($client);
        \Illuminate\Support\Facades\Mail::assertNothingSent();
    }

    public function test_dashboard_requires_dashboard_permission(): void
    {
        $this->actor(false);
        $this->expectException(AccessPermissionDeniedException::class);
        app(DashboardController::class)->dashboard(Request::create('/admin/dashboard'));
    }
}
