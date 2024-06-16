<?php

namespace Hitexis\Product\Models;

use Konekt\Concord\Proxies\ModelProxy;

class ProductPriceIndexProxy extends ModelProxy
{
    public static function modelClass()
    {
        return ProductPriceIndex::class;
    }
}
