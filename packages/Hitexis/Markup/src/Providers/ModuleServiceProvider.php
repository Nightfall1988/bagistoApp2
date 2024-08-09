<?php

namespace Hitexis\Markup\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Core\Providers\CoreModuleServiceProvider;
use Hitexis\Markup\Models\Markup;
use Hitexis\Markup\Models\MarkupProxy;
class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        Markup::class,
    ];

    public function boot(): void
    {
        parent::boot();

        $this->loadRoutesFrom(__DIR__ . '/../Http/routes.php');
        
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'markup');
        
        $this->publishes([
                 __DIR__ . '/../Resources/assets' => public_path('vendor/markup'),
            ], 'public');
    }

    public function register()
    {
        $this->app->register(EventServiceProvider::class);        
        $this->app->bind(Hitexis\Markup\Contracts\Markup::class, 
                         Hitexis\Markup\Repositories\MarkupRepository::class);
    }

}