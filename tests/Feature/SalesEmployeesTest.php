<?php
namespace Tests\Feature;

use App\Models\Admin;
use App\Services\EmployeeInvitationService;
use App\Services\SalesRoleService;
use App\Jobs\SendEmployeeInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SalesEmployeesTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void {
        parent::setUp();
        $this->withoutMiddleware([\Modules\Installer\app\Http\Middleware\SetupMiddleware::class, \Modules\Installer\app\Http\Middleware\PurchaseVerifyMiddleware::class]);
        config(['app.app_mode' => 'LIVE']);
        Cache::put('setting', (object) ['app_name' => 'MedFind', 'favicon' => '', 'logo' => '', 'default_avatar' => '', 'recaptcha_status' => 'inactive', 'version' => '1']);
        Mail::fake(); Queue::fake();
    }
    private function admin(bool $owner = false): Admin {
        $admin = new Admin;
        $admin->forceFill(['name' => $owner ? 'Owner' : 'Sales Person', 'email' => $owner ? 'owner@example.test' : 'sales@example.test', 'password' => Hash::make('OriginalPassword123!'), 'is_super_admin' => $owner, 'status' => 'active'])->save();
        if (!$owner) $admin->syncRoles([app(SalesRoleService::class)->ensure()]);
        return $admin;
    }
    public function test_sales_role_has_only_onboarding_permissions_and_blocks_direct_management_urls(): void {
        $employee = $this->admin(); $this->actingAs($employee, 'admin');
        foreach (['doctor','facility','school','supplier'] as $type) {
            foreach (['view','create','store'] as $action) $this->assertTrue($employee->can("$type.$action"));
            foreach (['delete','update'] as $action) $this->assertFalse($employee->can("$type.$action"));
        }
        $this->assertFalse($employee->can('setting.view'));
        $employee->givePermissionTo(\Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'setting.view', 'guard_name' => 'admin'], ['group_name' => 'setting']));
        foreach (['/admin/settings','/admin/test-mail','/admin/menu','/admin/faq-category','/admin/social-link','/admin/app/on-boarding-screen','/admin/provider-messages','/admin/admin','/admin/role','/admin/provider-payments'] as $url) $this->getJson($url)->assertForbidden();
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Sales dashboard')->assertDontSee('Manage Website')->assertDontSee('Test Mail');
        foreach (['doctor','clinic','school','supplier'] as $type) $this->get(route('admin.clients.create', ['type' => $type]))->assertOk();
    }
    public function test_owner_can_import_csv_and_duplicates_do_not_modify_existing_accounts(): void {
        $this->actingAs($this->admin(true), 'admin');
        $this->get(route('admin.employees.invite'))->assertOk();
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('sales.csv', "email,name\none@example.test,Sales One\ntwo@example.test,Sales Two\n");
        $this->post(route('admin.employees.invite.store'), ['csv' => $file])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(2, Admin::notSuperAdmin()->count());
        $this->assertTrue(Admin::where('email','one@example.test')->first()->hasRole('Sales'));
        $before = Admin::where('email','one@example.test')->first()->password;
        $this->postJson(route('admin.employees.invite.store'), ['emails' => 'one@example.test,new@example.test'])->assertUnprocessable();
        $this->assertSame($before, Admin::where('email','one@example.test')->first()->password);
        $this->assertDatabaseMissing('admins', ['email' => 'new@example.test']);
        Queue::assertPushed(SendEmployeeInvitation::class, 2);
    }
    public function test_invitation_email_login_and_required_password_change(): void {
        $employee = app(EmployeeInvitationService::class)->invite('invited@example.test', 'Invited Sales');
        $job = Queue::pushed(SendEmployeeInvitation::class)->first();
        $this->assertNotNull($job);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldBeEncrypted::class, $job);
        $job->handle();
        Mail::assertSent(\App\Mail\EmployeeInvitation::class, fn ($mail) => $mail->hasTo($employee->email) && $mail->temporaryPassword === $job->temporaryPassword);
        $this->assertNotNull($employee->fresh()->invitation_sent_at);
        $this->post(route('admin.store-login'), ['email' => $employee->email, 'password' => $job->temporaryPassword])->assertRedirect(route('admin.employee-password.edit'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.employee-password.edit'));
        $this->get(route('admin.employee-password.edit'))->assertOk()->assertSee('Choose your employee password');
        $this->put(route('admin.employee-password.update'), ['current_password' => $job->temporaryPassword, 'password' => 'MyNewPassword123!', 'password_confirmation' => 'MyNewPassword123!'])->assertRedirect(route('admin.dashboard'));
        $this->assertFalse($employee->fresh()->must_change_password);
        $this->assertFalse(Hash::check($job->temporaryPassword, $employee->fresh()->password));
        $this->get(route('admin.dashboard'))->assertOk();
        $this->postJson(route('admin.employees.invite.store'), ['emails' => 'rogue@example.test'])->assertForbidden();
    }
    public function test_expired_password_is_rejected_and_resend_invalidates_old_queued_email(): void {
        $service = app(EmployeeInvitationService::class);
        $employee = $service->invite('expired@example.test');
        $old = Queue::pushed(SendEmployeeInvitation::class)->first();
        $employee->forceFill(['temporary_password_expires_at' => now()->subMinute()])->save();
        $this->post(route('admin.store-login'), ['email' => $employee->email, 'password' => $old->temporaryPassword])->assertSessionHasErrors('email');
        $this->assertGuest('admin');
        $service->invite($employee->email, $employee->name, $employee);
        $old->handle(); Mail::assertNothingSent();
        $this->assertFalse(Hash::check($old->temporaryPassword, $employee->fresh()->password));
    }

    public function test_sales_employees_can_onboard_all_four_client_types_and_are_recorded_as_creator(): void {
        $employee = $this->admin(); $this->actingAs($employee, 'admin');
        \Illuminate\Support\Facades\DB::table('basic_payments')->updateOrInsert(['key' => 'paystack_status'], ['value' => 'active']);
        \Illuminate\Support\Facades\DB::table('basic_payments')->updateOrInsert(['key' => 'paystack_secret_key'], ['value' => 'test-secret']);
        \Illuminate\Support\Facades\Http::fake(['api.paystack.co/transaction/initialize' => \Illuminate\Support\Facades\Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/test', 'access_code' => 'test-code']])]);
        $department = \Modules\Doctor\app\Models\Department::create(['slug' => 'demo-specialty']);
        $location = \Modules\Doctor\app\Models\Location::create(['status' => 1]);
        foreach (['doctor','clinic','school','supplier'] as $type) {
            $plan = app(\Modules\Doctor\app\Services\SubscriptionPlanService::class)->for($type)[0];
            $this->postJson(route('admin.clients.store'), ['type' => $type, 'name' => 'Sales Client', 'email' => $type.'@example.test', 'phone' => '0700000000', 'package' => $plan['id'], 'department_id' => $department->id, 'location_id' => $location->id])->assertRedirect()->assertSessionHasNoErrors();
            $this->assertDatabaseHas('account_creation_audits', ['provider_type' => $type, 'client_email' => $type.'@example.test', 'source' => 'employee', 'creator_admin_id' => $employee->id]);
        }
        Mail::assertQueued(\App\Mail\ClientPaymentInvitation::class, 4);
    }
}
