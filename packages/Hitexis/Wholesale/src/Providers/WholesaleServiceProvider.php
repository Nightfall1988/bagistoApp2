<?php

namespace Hitexis\Wholesale\Providers;

use Hitexis\Wholesale\Repositories\WholesaleRepository;
use Illuminate\Support\ServiceProvider;

class WholesaleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
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
