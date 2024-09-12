<?php

namespace Hitexis\Shop\Http\Controllers\API;

use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Category\Repositories\CategoryRepository;
use Hitexis\Marketing\Jobs\UpdateCreateSearchTerm as UpdateCreateSearchTermJob;
use Hitexis\Product\Repositories\HitexisProductRepository as ProductRepository;
use Hitexis\Shop\Http\Resources\ProductResource;

class ProductController extends APIController
{
    /**
     * Create a controller instance.
     *
     * @return void
     */
    public function __construct(
        protected CategoryRepository $categoryRepository,
        protected ProductRepository $productRepository
    ) {
    }

    /**
     * Product listings.
     */
    public function index(): JsonResource
    {
        if (! empty(request()->query('query'))) {
            $products = $this->productRepository->getAll(request()->query());
            /**
             * Update or create search term only if
             * there is only one filter that is query param
             */
            if (count(request()->except(['mode', 'sort', 'limit'])) == 1) {
                UpdateCreateSearchTermJob::dispatch([
                    'term'       => request()->query('query'),
                    'results'    => $products->total(),
                    'channel_id' => core()->getCurrentChannel()->id,
                    'locale'     => app()->getLocale(),
                ]);
            }
        } else {
            $products = $this->productRepository->getCategoryProducts(request()->query());
        }

        return ProductResource::collection($products);
    }

    /**
     * Related product listings.
     *
     * @param  int  $id
     */
    public function relatedProducts($id): JsonResource
    {
        $product = $this->productRepository->findOrFail($id);

        $relatedProducts = $product->related_products()
            ->take(core()->getConfigData('catalog.products.product_view_page.no_of_related_products'))
            ->get();

        return ProductResource::collection($relatedProducts);
    }

    /**
     * Up-sell product listings.
     *
     * @param  int  $id
     */
    public function upSellProducts($id): JsonResource
    {
        $product = $this->productRepository->findOrFail($id);

        $upSellProducts = $product->up_sells()
            ->take(core()->getConfigData('catalog.products.product_view_page.no_of_up_sells_products'))
            ->get();

        return ProductResource::collection($upSellProducts);
    }

    public function getVariantSku($parentProdId, $attributeCode, $attributeName) {
        $sku = '';
        $product = $this->productRepository->findOrFail($parentProdId);

        $variants = $this->productRepository->findWhere([
            'parent_id' => $parentProdId,
        ]);

        foreach ($variants as $variant) {
            if ($variant->$attributeCode == $attributeName) {
                $sku = $variant->sku;
            }
        }
        return json_encode(['sku' => $sku]);
    }
}
