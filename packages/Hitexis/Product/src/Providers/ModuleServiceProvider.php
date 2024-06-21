<?php

namespace Hitexis\Product\Providers;

use Konekt\Concord\BaseModuleServiceProvider;
use Hitexis\Product\Models\Product;
use Hitexis\Product\Repositories\HitexisProductRepository;
use Hitexis\Product\Contracts\Product as ProductContract;
use Hitexis\Product\Models\Product as HitexisProduct;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        HitexisProduct::class,
        \Hitexis\Product\Models\ProductAttributeValue::class,
        \Hitexis\Product\Models\ProductBundleOption::class,
        \Hitexis\Product\Models\ProductBundleOptionProduct::class,
        \Hitexis\Product\Models\ProductBundleOptionTranslation::class,
        \Hitexis\Product\Models\ProductCustomerGroupPrice::class,
        \Hitexis\Product\Models\ProductDownloadableLink::class,
        \Hitexis\Product\Models\ProductDownloadableSample::class,
        \Hitexis\Product\Models\ProductFlat::class,
        \Hitexis\Product\Models\ProductGroupedProduct::class,
        \Hitexis\Product\Models\ProductImage::class,
        \Hitexis\Product\Models\ProductInventory::class,
        \Hitexis\Product\Models\ProductInventoryIndex::class,
        \Hitexis\Product\Models\ProductOrderedInventory::class,
        \Hitexis\Product\Models\ProductPriceIndex::class,
        \Hitexis\Product\Models\ProductReview::class,
        \Hitexis\Product\Models\ProductReviewAttachment::class,
        \Hitexis\Product\Models\ProductSalableInventory::class,
        \Hitexis\Product\Models\ProductVideo::class,
    ];
    public function boot()
    {
        parent::boot();

        // $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'product');

        // Load custom routes
        // $this->loadRoutesFrom(__DIR__ . '/../../Http/routes.php');
    }

    public function register()
    {
        $this->app->bind(ProductContract::class, Product::class);
        $this->app->bind('HitexisProductRepository', function ($app) {
            return new HitexisProductRepository(new HitexisProduct());
        });
    }
}
