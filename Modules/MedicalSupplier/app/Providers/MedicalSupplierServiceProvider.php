<?php

namespace Modules\MedicalSupplier\app\Providers;

use Illuminate\Support\ServiceProvider;

class MedicalSupplierServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'MedicalSupplier';

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
    }
}
