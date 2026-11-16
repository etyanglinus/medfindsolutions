<?php

namespace App\Console\Commands;

use App\Models\ProviderProfile;
use Illuminate\Console\Command;
use Modules\Doctor\app\Models\Clinic;
use Modules\Doctor\app\Models\Doctor;

class SyncProviderRegistry extends Command
{
    protected $signature = 'providers:sync-registry {--dry-run : Report records without creating registry rows}';

    protected $description = 'Register existing Doctor and Clinic records in the shared provider registry';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $created = 0;
        $existing = 0;

        Doctor::query()->select('id')->chunkById(100, function ($doctors) use ($dryRun, &$created, &$existing) {
            foreach ($doctors as $doctor) {
                $this->syncProvider(ProviderProfile::TYPE_SPECIALIST, $doctor->id, $dryRun, $created, $existing);
            }
        });

        Clinic::query()->select('id')->chunkById(100, function ($clinics) use ($dryRun, &$created, &$existing) {
            foreach ($clinics as $clinic) {
                $this->syncProvider(ProviderProfile::TYPE_FACILITY, $clinic->id, $dryRun, $created, $existing);
            }
        });

        $this->info(($dryRun ? 'Dry run: ' : '') . "{$created} registry rows to create; {$existing} already registered.");

        return self::SUCCESS;
    }

    private function syncProvider(string $type, int $providerId, bool $dryRun, int &$created, int &$existing): void
    {
        $query = ProviderProfile::query()
            ->where('provider_type', $type)
            ->where('provider_id', $providerId);

        if ($query->exists()) {
            $existing++;
            return;
        }

        $created++;
        if (! $dryRun) {
            ProviderProfile::create([
                'provider_type' => $type,
                'provider_id' => $providerId,
                'status' => ProviderProfile::STATUS_DRAFT,
                'verification_status' => ProviderProfile::VERIFICATION_UNVERIFIED,
            ]);
        }
    }
}
