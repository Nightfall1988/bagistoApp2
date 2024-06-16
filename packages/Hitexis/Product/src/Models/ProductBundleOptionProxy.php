<?php
namespace Hitexis\Product\Models;

use Konekt\Concord\Proxies\ModelProxy;

class ProductBundleOptionProxy extends ModelProxy
{
    public static function modelClass()
    {
        return ProductBundleOption::class;
    }
}
