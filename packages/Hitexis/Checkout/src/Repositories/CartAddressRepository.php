<?php

namespace Hitexis\Checkout\Repositories;

use Webkul\Core\Eloquent\Repository;

class CartAddressRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Hitexis\Checkout\Contracts\CartAddress';
    }
}
