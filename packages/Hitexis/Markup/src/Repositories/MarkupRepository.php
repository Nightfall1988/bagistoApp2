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
use Webkul\Product\Models\ProductFlat;

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

        if($data['percentage']) {
            $data["markup_unit"] = 'percent';
        }

        if($data['amount']) {
            $data["markup_unit"] = 'amount';
        }

        $data['currency'] = 'EUR'; // GET DEFAULT LOCALE
        $markup = parent::create($data);

        if (isset($data['product_id']) && $data['markup_type'] == 'individual') {
            $product = $this->productRepository->where('id', $data['product_id'])->first();
            $product->markup()->attach($markup->id);
            $this->addMarkupToPrice($product,$markup);
        } else {
            $products = Product::all();

            if (!$products->isEmpty()) {
                foreach($products as $product) {
                    $this->addMarkupToPrice($product, $markup);
                }
            }
        }
        
        return $markup;
    }

    public function addMarkupToPrice($product, $markup) 
    {
        $cost =  $this->productAttributeValueRepository->findOneWhere([
            'product_id'   => $product->id,
            'attribute_id' => 12,
        ]);

        $price =  $this->productAttributeValueRepository->findOneWhere([
            'product_id'   => $product->id,
            'attribute_id' => 11,
        ]);

        if ($cost) {
            if ($markup->percentage) {
                $priceMarkup = $cost->float_value * ($markup->percentage/100);
            }

            if ($markup->amount) {
                $priceMarkup = $markup->amount;
            }

            if ($product->type == 'simple') {
                $productFlat = ProductFlat::where('product_id',  $product->id)->first();
                $newPrice = $cost->float_value + $priceMarkup;
                $newPrice = round($newPrice, 2);
                $product->markup()->attach($markup->id);

                $price->float_value = $newPrice;
                $price->save();

                if ($productFlat) {
                    $productFlat->price = $newPrice;
                    $productFlat->save();
                }

            } else {
                foreach ($product->variants as $product) {
                    $cost = $this->productAttributeValueRepository->findOneWhere([
                        'product_id'   => $product->id,
                        'attribute_id' => 12,
                    ]);

                    $price = $this->productAttributeValueRepository->findOneWhere([
                        'product_id'   => $product->id,
                        'attribute_id' => 11,
                    ]);

                    if ($cost) {
                        $product->markup()->attach($markup->id);
                        $productFlat = ProductFlat::where('product_id',  $product->id)->first();
                        $newPrice = $cost->float_value + $priceMarkup;
                        $newPrice = round($newPrice, 2);

                        $price->float_value = $newPrice;
                        $price->save();
        
                        $price->float_value = $newPrice;
                        $price->save();
                        
                        if ($productFlat) {
                            $productFlat->price = $newPrice;
                            $productFlat->save();
                        }
                    }
                }
            }
        }
    }

    public function subtractMarkupFromPrice($product, $markup) 
    {
        $cost =  $this->productAttributeValueRepository->findOneWhere([
            'product_id'   => $product->id,
            'attribute_id' => 12,
        ]);

        $price = $this->productAttributeValueRepository->findOneWhere([
            'product_id'   => $product->id,
            'attribute_id' => 11,
        ]);

        if ($product->type == 'simple' && $cost != null) {
            if ($markup->percentage) {
                $priceMarkup = $cost->float_value * ($markup->percentage/100);
            }
    
            if ($markup->amount) {
                $priceMarkup = $markup->amount;
            }
            
            $newPrice = $cost->float_value;
            $product->markup()->detach($markup->id);
            $productFlat = ProductFlat::where('product_id', $product->id)->first();
            $newPrice = $price->float_value - $priceMarkup;
            $newPrice = round($newPrice, 2);

            if ($productFlat) {
                $productFlat->price = $newPrice;
                $productFlat->save();

                $price->float_value = $newPrice;
                $price->save();
            }
        }
        
        if ($product->type == 'configurable' && $cost != null) {
            $price = 0;
            foreach ($product->variants as $productVar) {
                if ($markup->percentage) {
                    $priceMarkup = $cost->float_value * ($markup->percentage/100);
                }
        
                if ($markup->amount) {
                    $priceMarkup = $markup->amount;
                }

                $productVar->markup()->detach($markup->id);
                $productFlat = ProductFlat::where('product_id',  $productVar->id)->first();
                $newPrice = $price->float_value - $priceMarkup;
                $price = round($newPrice, 2);

                if ($productFlat) {
                    $price->float_value = $newPrice;

                    $price->save();

                    $productFlat->price = $newPrice;
                    $productFlat->save();
                }
            }

            if ($markup->percentage) {
                $priceMarkup = $cost->float_value * ($markup->percentage/100);
            }
    
            if ($markup->amount) {
                $priceMarkup = $markup->amount;
            }
            
            $product->markup()->detach($markup->id);
            $productFlat = ProductFlat::where('product_id',  $product->id)->first();
            $newPrice = $price->float_value - $priceMarkup;
            $newPrice = round($newPrice, 2);

            if ($productFlat) {
                $price->float_value = $newPrice;
                $price->save();

                $productFlat->price = $newPrice;
                $productFlat->save();
            }
        }
    }

    public function calculatePrice($cost, $markup = null) {
        if (!$markup) {
            return round($cost, 2);
        } else {
            return round($cost + $cost * ($markup->percentage/100), 2);
        }
    }
}