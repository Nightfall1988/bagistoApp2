<?php

namespace Hitexis\Markup\Repositories;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Eloquent\Repository;
use Hitexis\Markup\Contracts\Markup as MarkupContract;

class MarkupRepository extends Repository implements MarkupContract
{
    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(
        Container $container
    ) {
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

        $deal = parent::create($data);

        foreach ($data as $key => $value) {
            $deal->$key = $value;
        }

        $deal->products()->attach($deal->product_id);
        
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