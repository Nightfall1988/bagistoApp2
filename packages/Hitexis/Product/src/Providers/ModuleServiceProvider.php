<?php

namespace Hitexis\Product\Providers;

use Konekt\Concord\BaseModuleServiceProvider;
use Hitexis\Product\Models\Product;
use Hitexis\Product\Repositories\ProductRepository;
use Hitexis\Product\Contracts\Product as ProductContract;
use Hitexis\Product\Models\Product as HitexisProduct;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        HitexisProduct::class,
        \Webkul\Product\Models\ProductAttributeValue::class,
        \Webkul\Product\Models\ProductBundleOption::class,
        \Webkul\Product\Models\ProductBundleOptionProduct::class,
        \Webkul\Product\Models\ProductBundleOptionTranslation::class,
        \Webkul\Product\Models\ProductCustomerGroupPrice::class,
        \Webkul\Product\Models\ProductDownloadableLink::class,
        \Webkul\Product\Models\ProductDownloadableSample::class,
        \Webkul\Product\Models\ProductFlat::class,
        \Webkul\Product\Models\ProductGroupedProduct::class,
        \Webkul\Product\Models\ProductImage::class,
        \Webkul\Product\Models\ProductInventory::class,
        \Webkul\Product\Models\ProductInventoryIndex::class,
        \Webkul\Product\Models\ProductOrderedInventory::class,
        \Webkul\Product\Models\ProductPriceIndex::class,
        \Webkul\Product\Models\ProductReview::class,
        \Webkul\Product\Models\ProductReviewAttachment::class,
        \Webkul\Product\Models\ProductSalableInventory::class,
        \Webkul\Product\Models\ProductVideo::class,
    ];
    public function boot()
    {
        parent::boot();

        $this->loadViewsFrom(__DIR__ . '/../../Resources/views', 'product');

        // Load custom routes
        $this->loadRoutesFrom(__DIR__ . '/../../Http/routes.php');
    }

    public function register()
    {
        $this->app->bind(ProductContract::class, Product::class);
        $this->app->bind('ProductRepository', function ($app) {
            return new ProductRepository(new Product());
        });
    }
}
