<?php

namespace Hitexis\Wholesale\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Http/wholesale-routes.php');
        
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