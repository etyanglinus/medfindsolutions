<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\AccountCreationAudit;
use App\Models\ProviderProfile;
use App\Models\Admin;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('account_creation_audits', function (Blueprint $table) {
            $table->id();
            $table->string('provider_type', 30);
            $table->unsignedBigInteger('provider_id');
            $table->string('client_name');
            $table->string('client_email')->nullable();
            $table->string('source', 30)->index();
            $table->unsignedBigInteger('creator_admin_id')->nullable()->index();
            $table->string('creator_name')->nullable();
            $table->string('creator_email')->nullable();
            $table->timestamp('created_at')->nullable()->index();
            $table->unique(['provider_type', 'provider_id']);
        });
        foreach (['doctor' => \Modules\Doctor\app\Models\Doctor::class, 'clinic' => \Modules\Doctor\app\Models\Clinic::class, 'school' => \Modules\HealthcareInstitution\app\Models\HealthcareInstitution::class, 'supplier' => \Modules\MedicalSupplier\app\Models\MedicalSupplier::class] as $type => $model) {
            $model::withoutGlobalScopes()->chunkById(100, function ($clients) use ($type) {
                foreach ($clients as $client) {
                    $profile = ProviderProfile::withoutGlobalScopes()->where('provider_type', $type)->where('provider_id', $client->id)->first();
                    $subscription = $profile?->subscriptions()->where('metadata->admin_onboarding', true)->oldest('id')->first();
                    $creatorId = data_get($subscription?->metadata, 'created_by');
                    $admin = $creatorId ? Admin::find($creatorId) : null;
                    AccountCreationAudit::firstOrCreate(['provider_type' => $type, 'provider_id' => $client->id], [
                        'client_name' => $client->name, 'client_email' => $client->email ?? $client->official_email,
                        'source' => $admin ? ($admin->is_super_admin ? 'admin' : 'employee') : 'unknown',
                        'creator_admin_id' => $creatorId, 'creator_name' => $admin?->name, 'creator_email' => $admin?->email,
                        'created_at' => $client->created_at,
                    ]);
                }
            });
        }
        $permission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'account.report.view', 'guard_name' => 'admin'], ['group_name' => 'reports']);
        \Spatie\Permission\Models\Role::where('name', 'Super Admin')->where('guard_name', 'admin')->get()->each(fn ($role) => $role->givePermissionTo($permission));
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void { Schema::dropIfExists('account_creation_audits'); }
};
