<?php
namespace Hitexis\Shop\Providers;

use Hitexis\Shop\Providers\ShopServiceProvider;

class HitexisServiceProvider extends ShopServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            'Hitexis\Shop\Http\Controllers\HitexisProductsCategoriesProxyController'
        );
    }
}
