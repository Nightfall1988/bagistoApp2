<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Hitexis\Product\Repositories\HitexisProductRepository;
use Hitexis\Product\Repositories\AttributeRepository;
use Webkul\Customer\Repositories\CustomerRepository;
use Hitexis\Product\Repositories\SearchSynonymRepository;
use Hitexis\Product\Repositories\ProductAttributeValueRepository;
use Hitexis\Product\Repositories\ElasticSearchRepository;
use Hitexis\Product\Repositories\AttributeOptionRepository;
use Illuminate\Container\Container;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        $this->app->singleton(HitexisProductRepository::class, function ($app) {
            return new HitexisProductRepository(
                $app->make(CustomerRepository::class),
                $app->make(AttributeRepository::class),
                $app->make(ProductAttributeValueRepository::class),
                $app->make(ElasticSearchRepository::class),
                $app->make(SearchSynonymRepository::class),
                $app->make(AttributeOptionRepository::class),
                $app->make(Container::class),
            );
        });

        // Bind the service
        $this->app->singleton(MidoceanApiService::class, function ($app) {
            return new MidoceanApiService($app->make(HitexisProductRepository::class));
        });
    }
}