<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Hitexis\Product\Repositories\HitexisProductRepository;
use Hitexis\Attribute\Repositories\AttributeRepository;
use Webkul\Customer\Repositories\CustomerRepository;
use Hitexis\Product\Repositories\SearchSynonymRepository;
use Hitexis\Product\Repositories\ProductAttributeValueRepository;
use Hitexis\Product\Repositories\ElasticSearchRepository;
use Hitexis\Attribute\Repositories\AttributeOptionRepository;
use Hitexis\Product\Repositories\SupplierRepository;
use Illuminate\Container\Container;
use Hitexis\Product\Repositories\ProductImageRepository;
use Hitexis\Product\Models\ProductImage;
use App\Observers\WholesaleObserver;
use Hitexis\Wholesale\Models\Wholesale;

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
        $this->app->singleton(ProductImageRepository::class, function ($app) {
            return new ProductImageRepository(
                $app->make(HitexisProductRepository::class),
                $app->make(Container::class) // Replace Container::class with the appropriate class if needed
            );
        });

        $this->app->singleton(AttributeOptionRepository::class, function ($app) {
            return new AttributeOptionRepository($app->make(Container::class));
        });

        $this->app->singleton(AttributeRepository::class, function ($app) {
            return new AttributeRepository(
                $app->make(AttributeOptionRepository::class),
                $app->make(Container::class)
            );
        });

        $this->app->singleton(MidoceanApiService::class, function ($app) {
            return new MidoceanApiService($app->make(HitexisProductRepository::class), $app->make(SupplierRepository::class), $app->make(ProductImageRepository::class));
        });

        $this->app->singleton(StrickerApiService::class, function ($app) {
            return new StrickerApiService($app->make(HitexisProductRepository::class), $app->make(SupplierRepository::class));
        });

        $this->app->singleton(XDConnectsApiService::class, function ($app) {
            return new XDConnectsApiService($app->make(HitexisProductRepository::class), $app->make(SupplierRepository::class));
        });

        $this->app->bind(AttributeOption::class, AttributeOptionRepository::class);
    }
    
    public function boot()
    {
        Wholesale::observe(WholesaleObserver::class);
    }
}