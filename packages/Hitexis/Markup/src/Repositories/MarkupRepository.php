<?php

namespace Hitexis\Markup\Repositories;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Eloquent\Repository;
use Hitexis\Markup\Contracts\Markup as MarkupContract;
use Hitexis\Product\Repositories\HitexisProductRepository;
use Hitexis\Product\Repositories\ProductAttributeValueRepository;
use Webkul\Product\Repositories\ProductFlatRepository;
use Hitexis\Product\Models\ProductAttributeValue;
use Hitexis\Product\Models\Product;
use Hitexis\Markup\Models\Markup;
use Webkul\Product\Models\ProductFlat;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class MarkupRepository extends Repository implements MarkupContract
{
    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(
        HitexisProductRepository $productRepository,
        ProductAttributeValueRepository $productAttributeValueRepository,
        Container $container
    ) {
        $this->productRepository = $productRepository;
        $this->productAttributeValueRepository = $productAttributeValueRepository;
        parent::__construct($container);
    }

    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Hitexis\Markup\Models\Markup';
    }

    /**
     * @return \Hitexis\Markup\Contracts\Markup
     */
    public function create(array $data)
    {
        if ($data['percentage']) {
            $data["markup_unit"] = 'percent';
        }

        if ($data['amount']) {
            $data["markup_unit"] = 'amount';
        }
    
        $data['currency'] = 'EUR'; // GET DEFAULT LOCALE
        $markup = parent::create($data);
    
        if (isset($data['product_id']) && $data['markup_type'] == 'individual') {
            $product = $this->productRepository->where('id', $data['product_id'])->first();
            $product->markup()->attach($markup->id);
        } else {
            $products = Product::all();
            // Pass the collection of products instead of converting to an array
            $this->updateProductPrices($products, $markup);
        }
    
        return $markup;
    }

    public function destroy($id) {
        $markup = $this->where('id', $id)->first();
        return $this->subtractMarkupFromPrice($markup);
    }
    
    public function updateProductPrices($products, $markup)
    {
        set_time_limit(600);
    
        $products = new \Illuminate\Database\Eloquent\Collection($products);
        $customerGroups = DB::table('customer_groups')->pluck('id')->toArray();
        $locales = ['en', 'lv'];
        $chunks = $products->chunk(500); // Adjust the chunk size based on available memory
    
        foreach ($chunks as $productChunk) {
            $productIds = $productChunk->pluck('id')->toArray();
    
            // Fetch only the required cost data for the current chunk
            $costs = ProductAttributeValue::where('attribute_id', 12)
                ->whereIn('product_id', $productIds)
                ->pluck('float_value', 'product_id');
    
            // Initialize batch arrays for each chunk
            $batchUpdateProductAttributeValues = [];
            $batchUpdateProductFlat = [];
            $batchUpdateProductPriceIndices = [];
            $markupProductRelations = [];
    
            foreach ($productChunk as $product) {
                $currentPrice = null;
                $priceMarkup = 0;
    
                // Handle configurable products differently
                if ($product->type === 'configurable') {
                    $variantIds = $product->variants->pluck('id')->toArray();
                    $currentPrice = ProductAttributeValue::where('attribute_id', 12)
                        ->whereIn('product_id', $variantIds)
                        ->min('float_value'); // Minimum price among variants' costs
                } else {
                    $currentPrice = $costs[$product->id] ?? null;
                }
    
                if ($currentPrice !== null && floatval($currentPrice) != 0) {
                    // Apply markup to calculate the new price
                    $priceMarkup = $markup->percentage 
                        ? $currentPrice * ($markup->percentage / 100)
                        : ($markup->amount ?? 0);
    
                    $newPrice = $currentPrice + $priceMarkup;
    
                    // Add entries to batch arrays for each locale
                    foreach ($locales as $locale) {
                        $batchUpdateProductAttributeValues[] = [
                            'product_id'   => $product->id,
                            'attribute_id' => 11,
                            'locale'       => $locale,
                            'channel'      => 'default',
                            'float_value'  => round($newPrice, 2),
                        ];
    
                        $batchUpdateProductFlat[] = [
                            'product_id' => $product->id,
                            'locale'     => $locale,
                            'channel'    => 'default',
                            'price'      => round($newPrice, 2),
                        ];
                    }
    
                    // Prepare updates for product_price_indices across customer groups
                    foreach ($customerGroups as $groupId) {
                        $batchUpdateProductPriceIndices[] = [
                            'product_id'         => $product->id,
                            'customer_group_id'  => $groupId,
                            'channel_id'         => 1,
                            'min_price'          => round($newPrice, 2),
                            'regular_min_price'  => round($newPrice, 2),
                            'max_price'          => round($newPrice, 2),
                            'regular_max_price'  => round($newPrice, 2),
                        ];
                    }
    
                    // Add to product-markup relationship batch
                    $markupProductRelations[] = [
                        'product_id' => $product->id,
                        'markup_id'  => $markup->id,
                    ];
                }
            }
    
            // Batch upserts for the current chunk
            if (!empty($batchUpdateProductAttributeValues)) {
                DB::table('product_attribute_values')->upsert(
                    $batchUpdateProductAttributeValues,
                    ['product_id', 'attribute_id', 'locale', 'channel'],
                    ['float_value']
                );
            }
    
            if (!empty($batchUpdateProductFlat)) {
                DB::table('product_flat')->upsert(
                    $batchUpdateProductFlat,
                    ['product_id', 'locale', 'channel'],
                    ['price']
                );
            }
    
            if (!empty($batchUpdateProductPriceIndices)) {
                DB::table('product_price_indices')->upsert(
                    $batchUpdateProductPriceIndices,
                    ['product_id', 'customer_group_id', 'channel_id'],
                    ['min_price', 'regular_min_price', 'max_price', 'regular_max_price']
                );
            }
    
            if (!empty($markupProductRelations)) {
                DB::table('markup_product')->insert($markupProductRelations);
            }
    
            // Clear memory for each chunk
            unset($batchUpdateProductAttributeValues, $batchUpdateProductFlat, $batchUpdateProductPriceIndices, $markupProductRelations);
            gc_collect_cycles(); // Optimize memory usage
        }
    
        // Clear cache after processing all chunks
        \Artisan::call('cache:clear');
        \Artisan::call('view:clear');
    
        return response()->json([
            'status' => 'success',
            'message' => 'Prices successfully updated with cumulative markups!',
        ]);
    }

    public function subtractMarkupFromPrice($markup)
    {
        set_time_limit(600);
        $customerGroups = DB::table('customer_groups')->pluck('id')->toArray();
        $locales = ['en', 'lv'];
    
        // Fetch related products and chunk them for processing
        $products = $markup->products()->select('products.id', 'products.type')->get();
        $chunks = $products->chunk(500);
    
        foreach ($chunks as $productChunk) {
            $productIds = $productChunk->pluck('id')->toArray();

            // Pre-fetch necessary attribute data for this chunk
            $costs = ProductAttributeValue::where('attribute_id', 12)
                ->whereIn('product_id', $productIds)
                ->pluck('float_value', 'product_id');
    
            $prices = ProductAttributeValue::where('attribute_id', 11)
                ->whereIn('product_id', $productIds)
                ->pluck('float_value', 'product_id');
    
            // Batch arrays for database upserts
            $batchUpdateProductAttributeValues = [];
            $batchUpdateProductFlat = [];
            $batchUpdateProductPriceIndices = [];
    
            $configurableProductMinPrices = []; // To store the minimum price for each configurable product

            foreach ($productChunk as $product) {
                $currentPrice = $prices[$product->id] ?? null;
                $cost = $costs[$product->id] ?? null;
    
                if ($currentPrice === null || $currentPrice == 0) {
                    continue;
                }
    
                $markupAmount = $markup->percentage 
                    ? ($currentPrice * ($markup->percentage / 100)) 
                    : ($markup->amount ?? 0);
    

                $newPrice = max($currentPrice - $markupAmount, $cost ?? 0);

                // If the product is a variant of a configurable product, track its price
                if ($product->type == 'configurable') {
                    $configurableProductMinPrices[$product->id] = $product->variants[0]->cost;
                }
                // Prepare updates for simple and variant products
                foreach ($locales as $locale) {
                    $batchUpdateProductAttributeValues[] = [
                        'product_id'   => $product->id,
                        'attribute_id' => 11,
                        'locale'       => $locale,
                        'channel'      => 'default',
                        'float_value'  => round($newPrice, 2),
                    ];
    
                    $batchUpdateProductFlat[] = [
                        'product_id' => $product->id,
                        'locale'     => $locale,
                        'channel'    => 'default',
                        'price'      => round($newPrice, 2),
                    ];
                }

                foreach ($customerGroups as $groupId) {
                    $batchUpdateProductPriceIndices[] = [
                        'product_id'         => $product->id,
                        'customer_group_id'  => $groupId,
                        'channel_id'         => 1,
                        'min_price'          => round($newPrice, 2),
                        'regular_min_price'  => round($newPrice, 2),
                        'max_price'          => round($newPrice, 2),
                        'regular_max_price'  => round($newPrice, 2),
                    ];
                }
            }
    
            // Perform batch upserts for simple products and variants
            if (!empty($batchUpdateProductAttributeValues)) {
                DB::table('product_attribute_values')->upsert(
                    $batchUpdateProductAttributeValues,
                    ['product_id', 'attribute_id', 'locale', 'channel'],
                    ['float_value']
                );
            }
    
            if (!empty($batchUpdateProductFlat)) {
                DB::table('product_flat')->upsert(
                    $batchUpdateProductFlat,
                    ['product_id', 'locale', 'channel'],
                    ['price']
                );
            }
    
            if (!empty($batchUpdateProductPriceIndices)) {
                DB::table('product_price_indices')->upsert(
                    $batchUpdateProductPriceIndices,
                    ['product_id', 'customer_group_id', 'channel_id'],
                    ['min_price', 'regular_min_price', 'max_price', 'regular_max_price']
                );
            }
    
            // Update configurable products with the minimum variant price if available

            foreach ($configurableProductMinPrices as $configurableProductId => $minVariantPrice) {
                if ($minVariantPrice !== null && $minVariantPrice != 0) { // Ensure min price is valid
                    foreach ($locales as $locale) {
                        $batchUpdateProductAttributeValues[] = [
                            'product_id'   => $configurableProductId,
                            'attribute_id' => 11,
                            'locale'       => $locale,
                            'channel'      => 'default',
                            'float_value'  => round($minVariantPrice, 2),
                        ];
    
                        $batchUpdateProductFlat[] = [
                            'product_id' => $configurableProductId,
                            'locale'     => $locale,
                            'channel'    => 'default',
                            'price'      => round($minVariantPrice, 2),
                        ];
                    }
    
                    foreach ($customerGroups as $groupId) {
                        $batchUpdateProductPriceIndices[] = [
                            'product_id'        => $configurableProductId,
                            'customer_group_id' => $groupId,
                            'channel_id'        => 1,
                            'min_price'         => round($minVariantPrice, 2),
                            'regular_min_price' => round($minVariantPrice, 2),
                            'max_price'         => round($minVariantPrice, 2),
                            'regular_max_price' => round($minVariantPrice, 2),
                        ];
                    }
                }
            }
    
            // Perform final upserts for configurable products
            if (!empty($batchUpdateProductAttributeValues)) {
                DB::table('product_attribute_values')->upsert(
                    $batchUpdateProductAttributeValues,
                    ['product_id', 'attribute_id', 'locale', 'channel'],
                    ['float_value']
                );
            }
    
            if (!empty($batchUpdateProductFlat)) {
                DB::table('product_flat')->upsert(
                    $batchUpdateProductFlat,
                    ['product_id', 'locale', 'channel'],
                    ['price']
                );
            }
    
            if (!empty($batchUpdateProductPriceIndices)) {
                DB::table('product_price_indices')->upsert(
                    $batchUpdateProductPriceIndices,
                    ['product_id', 'customer_group_id', 'channel_id'],
                    ['min_price', 'regular_min_price', 'max_price', 'regular_max_price']
                );
            }
    
            // Clear memory after each chunk
            unset($batchUpdateProductAttributeValues, $batchUpdateProductFlat, $batchUpdateProductPriceIndices, $configurableProductMinPrices);
            gc_collect_cycles();  // Force garbage collection to optimize memory usage
        }
    
        // Detach the markup and clear caches once
        DB::table('markup_product')->where('markup_id', $markup->id)->delete();
        \Artisan::call('cache:clear');
        \Artisan::call('view:clear');
    
        $this->delete($markup->id);
    
        return response()->json([
            'status' => 'success',
            'message' => 'Markup removed, prices and indices reverted successfully!',
        ]);
    }
    
    protected function bulkUpdateProductPrices($updateData)
    {
        // Use bulk update for product prices
        $table = DB::table('product_attribute_values');

        foreach ($updateData as $data) {
            $table->where('product_id', $data['product_id'])
                ->where('attribute_id', $data['attribute_id'])
                ->update(['float_value' => $data['float_value']]);
        }
    }
}