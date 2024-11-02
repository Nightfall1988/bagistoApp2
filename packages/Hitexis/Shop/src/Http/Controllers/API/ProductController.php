<?php

namespace Hitexis\Shop\Http\Controllers\API;

use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Category\Repositories\CategoryRepository;
use Hitexis\Marketing\Jobs\UpdateCreateSearchTerm as UpdateCreateSearchTermJob;
use Hitexis\Product\Repositories\HitexisProductRepository as ProductRepository;
use Hitexis\Shop\Http\Resources\ProductResource;
use Illuminate\Http\Request;

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

    public function getVariantSku($parentProdId, Request $request) {
        $sku = '';
        $query = $request->query();

        $product = $this->productRepository->findOrFail($parentProdId);
        $variants = $this->productRepository->findWhere(['parent_id' => $parentProdId]);
    
        $matches = []; // Array to store matching variants

        foreach ($variants as $variant) {
            $isMatch = true; // Assume the variant is a match initially
        
            // Iterate over each attribute in the query and check if the variant matches all of them
            foreach ($query as $attributeCode => $attributeValue) {
                // If the variant does not have the attribute or the value does not match, set isMatch to false
                if (!property_exists($variant, $attributeCode) || $variant->$attributeCode != $attributeValue) {
                    $isMatch = false;
                    break; // Stop checking further if any attribute doesn't match
                }
            }
        
            // If all provided attributes matched, add the variant to the matches array
            // dd($variant->color, $variant->size);
            if ($isMatch) {
                $matches[] = $variant;
            }
        }

        // Return the first match if both attributes are given, or all matches if only one attribute is provided
        if (count($query) > 1) {
            // Return the first match that has both attributes
            $result = !empty($matches) ? $matches[0] : null;
        } else {

            $result = $matches;
        }
        
        // Return the SKU if found, or an empty string if not found
        return response()->json(['sku' => $sku]);
    }
}
