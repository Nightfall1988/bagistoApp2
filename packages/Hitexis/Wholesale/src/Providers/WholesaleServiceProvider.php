<?php

namespace Hitexis\Wholesale\Providers;

use Hitexis\Wholesale\Repositories\WholesaleRepository;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class WholesaleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'wholesale');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'hitexis-wholesale');

    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(WholesaleRepository::class);
    }
}
