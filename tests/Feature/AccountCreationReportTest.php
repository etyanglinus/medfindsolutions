<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AccountReportController;
use App\Models\AccountCreationAudit;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Modules\MedicalSupplier\app\Models\MedicalSupplier;
use Modules\Doctor\app\Models\SubscriptionPackage;
use Modules\Doctor\database\seeders\SubscriptionPackageSeeder;
use Tests\TestCase;

class AccountCreationReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_accounts_record_admin_employee_and_self_registration(): void
    {
        foreach (['admin', 'employee', 'self'] as $source) {
            if ($source !== 'self') {
                $admin = new Admin;
                $admin->forceFill(['name' => $source, 'email' => $source.'@example.test', 'password' => bcrypt('password'), 'status' => 'active', 'is_super_admin' => $source === 'admin'])->save();
                $this->actingAs($admin, 'admin');
            } else auth('admin')->logout();
            $supplier = MedicalSupplier::create(['name' => $source.' supplier', 'slug' => $source.'-supplier', 'official_email' => $source.'-supplier@example.test', 'password' => bcrypt('password')]);
            $this->assertDatabaseHas('account_creation_audits', ['provider_type' => 'supplier', 'provider_id' => $supplier->id, 'source' => $source]);
        }
        $this->assertSame('employee', AccountCreationAudit::where('source', 'employee')->first()->creator_name);
    }

    public function test_report_filters_and_csv_export_share_the_same_results(): void
    {
        $admin = new Admin;
        $admin->forceFill(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => bcrypt('password'), 'status' => 'active', 'is_super_admin' => 1])->save();
        $this->actingAs($admin, 'admin');
        AccountCreationAudit::create(['provider_type' => 'school', 'provider_id' => 1, 'client_name' => 'School', 'client_email' => 'school@example.test', 'source' => 'self', 'created_at' => now()]);
        AccountCreationAudit::create(['provider_type' => 'supplier', 'provider_id' => 1, 'client_name' => '=formula', 'client_email' => 'supplier@example.test', 'source' => 'employee', 'creator_admin_id' => 5, 'creator_name' => 'Staff', 'created_at' => now()]);
        $controller = app(AccountReportController::class);
        $request = Request::create('/report', 'GET', ['type' => 'supplier', 'source' => 'employee', 'creator_id' => 5]);
        $view = $controller->index($request);
        $this->assertSame(1, $view->getData()['accounts']->total());
        $request->merge(['export' => 'csv']);
        ob_start();
        $controller->index($request)->sendContent();
        $csv = ob_get_clean();
        $this->assertStringContainsString("'=formula", $csv);
        $this->assertStringNotContainsString('school@example.test', $csv);
    }

    public function test_public_registration_is_self_even_when_an_admin_is_logged_in(): void
    {
        $admin = new Admin;
        $admin->forceFill(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => bcrypt('password'), 'status' => 'active', 'is_super_admin' => 1])->save();
        $this->actingAs($admin, 'admin');
        $route = (new \Illuminate\Routing\Route('POST', 'provider/register', fn () => null))->name('provider.register');
        request()->setRouteResolver(fn () => $route);
        $client = MedicalSupplier::create(['name' => 'Self registered', 'slug' => 'self-register', 'official_email' => 'self@example.test', 'password' => bcrypt('password')]);
        $this->assertDatabaseHas('account_creation_audits', ['provider_type' => 'supplier', 'provider_id' => $client->id, 'source' => 'self', 'creator_admin_id' => null]);
    }

    public function test_report_is_restricted_to_authorized_staff(): void
    {
        $this->expectException(\App\Exceptions\AccessPermissionDeniedException::class);
        app(AccountReportController::class)->index(Request::create('/report'));
    }

    public function test_seeding_missing_plans_preserves_admin_edits_and_is_idempotent(): void
    {
        $seeder = app(SubscriptionPackageSeeder::class);
        $seeder->run();
        $count = SubscriptionPackage::count();
        $plan = SubscriptionPackage::firstOrFail();
        $plan->update(['name' => 'Custom plan', 'price' => 12345, 'is_active' => false]);
        $seeder->run();
        $this->assertSame($count, SubscriptionPackage::count());
        $this->assertSame('Custom plan', $plan->fresh()->name);
        $this->assertSame('12345.00', $plan->fresh()->price);
        $this->assertFalse($plan->fresh()->is_active);
        $this->assertSame(4, SubscriptionPackage::distinct()->count('provider_type'));
    }
}
