<?php

namespace Hitexis\Admin\Providers;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * @var string
     */
    protected $namespace = 'Hitexis\Wholesale\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        // parent::boot();
        $this->mapAdminRoutes();

    }

    /**
     * Define the routes for the application.
     */
    public function map()
    {
        // $this->mapAdminRoutes();
    }

    /**
     * Define the "admin" routes for the application.
     */
    protected function mapAdminRoutes()
    {
        Route::middleware('admin')
             ->namespace($this->namespace)
             ->group(base_path('packages/Hitexis/Admin/src/Routes/wholesale-routes.php'));
    }
}
