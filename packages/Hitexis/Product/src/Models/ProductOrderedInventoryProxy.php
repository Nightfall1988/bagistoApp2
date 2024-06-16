<?php

namespace Hitexis\Product\Models;

use Konekt\Concord\Proxies\ModelProxy;

class ProductOrderedInventoryProxy extends ModelProxy
{
    public static function modelClass()
    {
        return ProductOrderedInventory::class;
    }
}
