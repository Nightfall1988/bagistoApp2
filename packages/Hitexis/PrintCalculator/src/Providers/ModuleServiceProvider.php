<?php

namespace Hitexis\PrintCalculator\Providers;

use Konekt\Concord\BaseModuleServiceProvider;
use Hitexis\PrintCalculator\Models\PrintTechnique;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        PrintTechnique::class,
    ];

    public function boot(): void
    {
        parent::boot();

        $this->loadRoutesFrom(__DIR__ . '/../Http/routes.php');
        
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'printcalculator');
        
        $this->publishes([
                 __DIR__ . '/../Resources/assets' => public_path('vendor/printcalculator'),
            ], 'public');
    }

    public function register()
    {
        $this->app->bind(Hitexis\PrintCalculator\Contracts\PrintTechnique::class, 
                         Hitexis\PrintCalculator\Repositories\PrintTechniqueRepository::class);
    }
}