<?php

namespace App\Observers;

use App\Models\AccountCreationAudit;
use Illuminate\Database\Eloquent\Model;

class ClientCreationObserver
{
    public function created(Model $client): void
    {
        $routeName = request()->route()?->getName();
        $isAdminRequest = $routeName ? str_starts_with($routeName, 'admin.') : (str_starts_with(request()->path(), 'admin/') || app()->runningUnitTests());
        $admin = $isAdminRequest ? auth('admin')->user() : null;
        $type = $client->getMorphClass();
        AccountCreationAudit::create([
            'provider_type' => $type, 'provider_id' => $client->id,
            'client_name' => $client->name, 'client_email' => $client->email ?? $client->official_email,
            'source' => $admin ? ($admin->is_super_admin ? 'admin' : 'employee') : (app()->runningInConsole() && !app()->runningUnitTests() ? 'system' : 'self'),
            'creator_admin_id' => $admin?->id, 'creator_name' => $admin?->name,
            'creator_email' => $admin?->email, 'created_at' => $client->created_at ?? now(),
        ]);
    }
}
