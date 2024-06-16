<?php

namespace Hitexis\Product\Models;

use Konekt\Concord\Proxies\ModelProxy;

class ProductInventoryProxy extends ModelProxy
{
    public static function modelClass()
    {
        return ProductInventory::class;
    }
}
