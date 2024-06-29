<?php

namespace Hitexis\Wholesale\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Core\Providers\CoreModuleServiceProvider;
use Hitexis\Wholesale\Models\Wholesale;
use Hitexis\Wholesale\Models\WholesaleProxy;
class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        Wholesale::class,
    ];

    public function boot(): void
    {
        parent::boot();

        $this->loadRoutesFrom(__DIR__ . '/../Http/routes.php');
        
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'wholesale');
        
        $this->publishes([
                 __DIR__ . '/../Resources/assets' => public_path('vendor/wholesale'),
            ], 'public');
    }

    public function register()
    {
        $this->app->register(EventServiceProvider::class);        
        $this->app->bind(Hitexis\Wholesale\Contracts\Wholesale::class, 
                         Hitexis\Wholesale\Repositories\WholesaleRepository::class);
    }

}