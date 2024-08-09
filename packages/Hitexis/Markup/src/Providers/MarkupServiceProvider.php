<?php

namespace Hitexis\Markup\Providers;

use Hitexis\Markup\Repositories\MarkupRepository;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class MarkupServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'markup');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'hitexis-markup');

    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(MarkupRepository::class);
    }
}
