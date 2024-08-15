<?php

namespace Hitexis\Markup\Repositories;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Eloquent\Repository;
use Hitexis\Markup\Contracts\Markup as MarkupContract;
use Hitexis\Product\Repositories\HitexisProductRepository;

class MarkupRepository extends Repository implements MarkupContract
{
    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(
        HitexisProductRepository $productRepository,
        Container $container
    ) {
        $this->productRepository = $productRepository;
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
        $deal = parent::create($data);

        foreach ($data as $key => $value) {
            $deal->$key = $value;
        }

        if (isset($data['product_id']) && $data['markup_type'] == 'individual') {
            $product = $this->productRepository->where('id', $data['product_id'])->first();
            $product->markup()->attach($deal->id);
        }
        
        return $deal;
    }

    /**
     * @param  int  $id
     * @param  string  $attribute
     * @return \Hitexis\Wholesale\Contracts\Wholesale
     */
    public function update(array $data, $id, $attribute = 'id')
    {
        $markup = parent::update($data, $id);

        return $markup;
    }

}