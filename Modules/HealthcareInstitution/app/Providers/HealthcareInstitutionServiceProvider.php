<?php

namespace Modules\HealthcareInstitution\app\Providers;

use Illuminate\Support\ServiceProvider;

class HealthcareInstitutionServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'HealthcareInstitution';

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
    }
}
