<?php

namespace Hitexis\Markup\Repositories;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Eloquent\Repository;
use Hitexis\Markup\Contracts\Markup as MarkupContract;
use Hitexis\Product\Repositories\HitexisProductRepository;
use Hitexis\Product\Repositories\ProductAttributeValueRepository;


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

        foreach ($data as $key => $value) {
            $markup->$key = $value;
        }

        if (isset($data['product_id']) && $data['markup_type'] == 'individual') {
            $product = $this->productRepository->where('id', $data['product_id'])->first();
            $product->markup()->attach($markup->id);
            $this->addMarkupToPrice($product,$markup);
        }
        
        return $markup;
    }

public function addMarkupToPrice($product,$markup) 
    {
        // needs fix
        $cost = $product->getAttribute('cost');
        if ($markup->percentage) {
            $priceMarkup = $cost * ($markup->percentage/100);
        }

        if ($markup->amount) {
            $priceMarkup = $markup->amount;
        }

        if ($product->type == 'simple') {
            $productAttribute = $this->productAttributeValueRepository->findOneWhere([
                'product_id'   => $product->id,
                'attribute_id' => 11,
            ]);

            $productAttribute->float_value =+ $priceMarkup;
            $productAttribute->save();
            $product->markup()->attach($markup->id);
            $markup->product_id = $product->id;
        } else {
            foreach ($product->variants as $productVar) {
                $productAttribute = $this->productAttributeValueRepository->findOneWhere([
                    'product_id'   => $productVar->id,
                    'attribute_id' => 11,
                ]);
        
                $productAttribute->float_value = $productAttribute->float_value + $priceMarkup;
                $productAttribute->save();
                $productVar->markup()->attach($markup->id);
                $markup->product_id = $productVar->id;
            }

            $product->markup()->attach($markup->id);
        }
    }

    public function subtractMarkupFromPrice($product,$markup) 
    {
        // needs fix
        $cost = $product->getAttribute('cost');
        if ($markup->percentage) {
            $priceMarkup = $cost * ($markup->percentage/100);
        }

        if ($markup->amount) {
            $priceMarkup = $markup->amount;
        }

        if ($product->type == 'simple') {
            $productAttribute = $this->productAttributeValueRepository->findOneWhere([
                'product_id'   => $product->id,
                'attribute_id' => 11,
            ]);
    
            $productAttribute->float_value = $productAttribute->float_value - $priceMarkup;
            $productAttribute->save();
            $product->markup()->detach($markup->id);
        } else {
            foreach ($product->variants as $product) {
                $productAttribute = $this->productAttributeValueRepository->findOneWhere([
                    'product_id'   => $product->id,
                    'attribute_id' => 11,
                ]);
        
                $product->markup()->detach($markup->id);
                $productAttribute->save();
            }
        }
    }
}