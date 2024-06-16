<?php
namespace Hitexis\Product\Models;

use Konekt\Concord\Proxies\ModelProxy;

class ProductFlatProxy extends ModelProxy
{
    public static function modelClass()
    {
        return ProductFlat::class;
    }
}
