<?php

namespace Hitexis\Product\Models;

use Konekt\Concord\Proxies\ModelProxy;

class ProductCustomerGroupPriceProxy extends ModelProxy
{
    public static function modelClass()
    {
        return ProductCustomerGroupPrice::class;
    }
}
