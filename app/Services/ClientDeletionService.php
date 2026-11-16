<?php

namespace App\Services;

use App\Models\ProviderProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ClientDeletionService
{
    public function delete(Model $client, string $type): void
    {
        abort_unless(in_array($type, ['clinic', 'school', 'supplier']), 404);
        DB::transaction(function () use ($client, $type) {
            $client = $client->newQuery()->whereKey($client->id)->lockForUpdate()->firstOrFail();
            $profile = ProviderProfile::where('provider_type', $type)->where('provider_id', $client->id)->lockForUpdate()->first();
            if ($profile) {
                foreach ($profile->subscriptions()->whereIn('status', ['pending', 'approved'])->lockForUpdate()->get() as $subscription) {
                    $metadata = $subscription->metadata ?? [];
                    unset($metadata['login_secret']);
                    $metadata['deleted_by'] = auth('admin')->id();
                    $subscription->update(['status' => 'cancelled', 'cancelled_at' => now(), 'metadata' => $metadata]);
                }
                $profile->paymentTransactions()->whereIn('status', ['pending', 'initiated'])->update(['status' => 'failed', 'failure_reason' => 'Client account deleted.']);
                $profile->update(['status' => 'suspended', 'published_at' => null]);
                $profile->delete();
            }
            if ($type === 'clinic') $client->subscriptions()->where('status', 'pending')->update(['status' => 'rejected']);
            \Modules\Doctor\app\Models\ProviderDocument::where('provider_type', $type)->where('provider_id', $client->id)->where('status', 'pending')->update(['status' => 'rejected', 'admin_note' => 'Client account deleted.']);
            if ($type === 'supplier') $client->products()->update(['is_published' => false, 'is_featured' => false]);
            $client->delete();
        });
    }
}
