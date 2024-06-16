<?php

namespace Hitexis\Product\Models;

use Konekt\Concord\Proxies\ModelProxy;

class ProductVideoProxy extends ModelProxy
{
    public static function modelClass()
    {
        return ProductVideo::class;
    }
}
