<?php

namespace Hitexis\Admin\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Core\Tree;
use Webkul\Admin\Providers\AdminServiceProvider;

class HitexisAdminServiceProvider extends AdminServiceProvider
{
        /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        Route::middleware('web')->group(__DIR__.'/../Routes/web.php');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'admin');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'hitexis-admin');

        Blade::anonymousComponentPath(__DIR__.'/../Resources/views/components', 'hitexis-admin');
        // dd(\Hitexis\Admin\Http\Controllers\Catalog\ProductController::class);

        $this->app->bind(
            // \Webkul\Admin\Http\Controllers\Catalog\ProductController::class,
            \Hitexis\Admin\Http\Controllers\Catalog\ProductController::class
        );

        $this->composeView();

        $this->registerACL();

        $this->app->register(EventServiceProvider::class);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConfig();
    }
    /**
     * Register package config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/menu.php',
            'menu.admin'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/acl.php',
            'acl'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/system.php',
            'core'
        );
    }
}
