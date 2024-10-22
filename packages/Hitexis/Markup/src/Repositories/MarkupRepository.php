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
        set_time_limit(60);
        // Convert the collection explicitly to Eloquent collection
        $products = new \Illuminate\Database\Eloquent\Collection($products);
        
        // Split the collection into chunks manually
        $chunks = $products->chunk(1000); // Chunk the products manually
        
        foreach ($chunks as $productChunk) {
            
            $productIds = $productChunk->pluck('id')->toArray();
            
            // Fetch all necessary cost and price data for products in one query
            $costs = ProductAttributeValue::where('attribute_id', 12)
                ->whereIn('product_id', $productIds)
                ->pluck('float_value', 'product_id');
                
            $prices = ProductAttributeValue::where('attribute_id', 11)
                ->whereIn('product_id', $productIds)
                ->pluck('float_value', 'product_id');
            
            $locales = ['en', 'lv']; // Define locales to update
            
            $batchUpdateProductAttributeValues = [];
            $batchUpdateProductFlat = [];
            
            // Collect product-markup relationships for batch insert
            $markupProductRelations = [];
        
            // Process products within the chunk
            foreach ($productChunk as $product) {
                $currentPrice = null;
                $priceMarkup = 0;
        
                // Check for existing cost or price
                if (isset($costs[$product->id])) {
                    $cost = $costs[$product->id];
                    $currentPrice = $cost; // Start with cost if it exists
                } elseif (isset($prices[$product->id])) {
                    $currentPrice = $prices[$product->id]; // Fallback to price if cost doesn't exist
                }
        
                if ($currentPrice !== null) {
                    // Apply markup cumulatively to the current price
                    if ($markup->percentage) {
                        $priceMarkup = $currentPrice * ($markup->percentage / 100);
                    } elseif ($markup->amount) {
                        $priceMarkup = $markup->amount;
                    }
        
                    // Update the price cumulatively
                    $newPrice = $currentPrice + $priceMarkup;
        
                    // Prepare data for batch updates
                    foreach ($locales as $locale) {
                        // Update product_attribute_values
                        $batchUpdateProductAttributeValues[] = [
                            'product_id'   => $product->id,
                            'attribute_id' => 11,  // Price attribute_id
                            'locale'       => $locale,
                            'channel'      => 'default',
                            'float_value'  => round($newPrice, 2),
                        ];
        
                        // Update product_flat
                        $batchUpdateProductFlat[] = [
                            'product_id' => $product->id,
                            'locale'     => $locale,
                            'channel'    => 'default',
                            'price'      => round($newPrice, 2),
                        ];
                    }
                    
                    // Prepare product-markup association
                    $markupProductRelations[] = [
                        'product_id' => $product->id,
                        'markup_id'  => $markup->id,
                    ];
                }
            }
        
            // Batch upsert product_attribute_values
            if (!empty($batchUpdateProductAttributeValues)) {
                DB::table('product_attribute_values')->upsert(
                    $batchUpdateProductAttributeValues,
                    ['product_id', 'attribute_id', 'locale', 'channel'],  // Unique keys
                    ['float_value']  // Fields to update
                );
            }
        
            // Batch upsert product_flat
            if (!empty($batchUpdateProductFlat)) {
                DB::table('product_flat')->upsert(
                    $batchUpdateProductFlat,
                    ['product_id', 'locale', 'channel'],  // Unique keys
                    ['price']  // Fields to update
                );
            }
    
            // Efficiently associate markup with products in bulk
            if (!empty($markupProductRelations)) {
                DB::table('markup_product')->insert($markupProductRelations);
            }
        }
    
        // Clear cache
        \Artisan::call('cache:clear');
        \Artisan::call('view:clear');
    
        return response()->json([
            'status' => 'success',
            'message' => 'Prices successfully updated with cumulative markups!',
        ]);
    }

    public function subtractMarkupFromPrice($markup)
    {
        ini_set('max_execution_time', 300);
        // Fetch all related product IDs once and store them in an array
        $products = $markup->products()->select('products.id')->get();  // Specify 'products.id' explicitly
        
        // Convert the collection explicitly to Eloquent collection
        $products = new \Illuminate\Database\Eloquent\Collection($products);
    
        // Chunk the products for performance (reduce chunk size for quicker DB updates)
        $chunks = $products->chunk(250);
    
        // Collect all necessary costs and prices for products in one query
        $productIds = $products->pluck('id')->toArray();
        $costs = ProductAttributeValue::where('attribute_id', 12)
            ->whereIn('product_id', $productIds)
            ->pluck('float_value', 'product_id');
        $prices = ProductAttributeValue::where('attribute_id', 11)
            ->whereIn('product_id', $productIds)
            ->pluck('float_value', 'product_id');
    
        $locales = ['en', 'lv'];
    
        // Arrays for batch updates
        $batchUpdateProductAttributeValues = [];
        $batchUpdateProductFlat = [];
    
        // Loop through each chunk to process the products in smaller batches
        foreach ($chunks as $productChunk) {
            foreach ($productChunk as $product) {
                $currentPrice = $prices[$product->id] ?? null;
                $cost = $costs[$product->id] ?? null;
    
                // If no price exists, continue to the next product
                if ($currentPrice === null) {
                    continue;
                }
    
                // Subtract the markup from the price
                $markupAmount = 0;
                if ($markup->percentage) {
                    $markupAmount = $currentPrice * ($markup->percentage / 100);
                } elseif ($markup->amount) {
                    $markupAmount = $markup->amount;
                }
    
                // Subtract the markup from the price
                $newPrice = $currentPrice - $markupAmount;
    
                // Ensure the new price does not go below the cost (if cost exists)
                if ($cost && $newPrice <= $cost) {
                    $newPrice = $cost;
                }
    
                // Only update if the price actually changes
                if (round($newPrice, 2) != round($currentPrice, 2)) {
                    foreach ($locales as $locale) {
                        // Prepare data for batch update to product_attribute_values
                        $batchUpdateProductAttributeValues[] = [
                            'product_id'   => $product->id,
                            'attribute_id' => 11,  // Price attribute_id
                            'locale'       => $locale,
                            'channel'      => 'default',
                            'float_value'  => round($newPrice, 2),
                        ];
    
                        // Prepare data for batch update to product_flat
                        $batchUpdateProductFlat[] = [
                            'product_id' => $product->id,
                            'locale'     => $locale,
                            'channel'    => 'default',
                            'price'      => round($newPrice, 2),
                        ];
                    }
                }
    
                // Detach the markup from the product
                $product->markup()->detach($markup->id);
            }
    
            // Perform batch upserts for product_attribute_values
            if (!empty($batchUpdateProductAttributeValues)) {
                DB::table('product_attribute_values')->upsert(
                    $batchUpdateProductAttributeValues,
                    ['product_id', 'attribute_id', 'locale', 'channel'],  // Unique keys
                    ['float_value']  // Fields to update
                );
            }
    
            // Perform batch upserts for product_flat
            if (!empty($batchUpdateProductFlat)) {
                DB::table('product_flat')->upsert(
                    $batchUpdateProductFlat,
                    ['product_id', 'locale', 'channel'],  // Unique keys
                    ['price']  // Fields to update
                );
            }
    
            // Reset the batch arrays to avoid memory overload
            $batchUpdateProductAttributeValues = [];
            $batchUpdateProductFlat = [];
        }
    
        // Clear cache to reflect the updated prices
        \Artisan::call('cache:clear');
        \Artisan::call('view:clear');
        $this->delete($markup->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Markup removed and prices reverted successfully!',
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