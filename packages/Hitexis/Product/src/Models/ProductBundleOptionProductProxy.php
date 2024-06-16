<?php
namespace Hitexis\Product\Models;

use Konekt\Concord\Proxies\ModelProxy;

class ProductBundleOptionProductProxy extends ModelProxy
{
    public static function modelClass()
    {
        return ProductBundleOptionProduct::class;
    }
}
