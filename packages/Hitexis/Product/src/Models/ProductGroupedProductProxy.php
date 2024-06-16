<?php
namespace Hitexis\Product\Models;

use Konekt\Concord\Proxies\ModelProxy;

class ProductGroupedProductProxy extends ModelProxy
{
    public static function modelClass()
    {
        return ProductGroupedProduct::class;
    }
}
