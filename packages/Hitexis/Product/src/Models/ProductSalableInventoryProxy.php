<?php
namespace Hitexis\Product\Models;

use Konekt\Concord\Proxies\ModelProxy;

class ProductSalableInventoryProxy extends ModelProxy
{
    public static function modelClass()
    {
        return ProductSalableInventory::class;
    }
}
