<?php
namespace Hitexis\Product\Models;

use Konekt\Concord\Proxies\ModelProxy;

class ProductInventoryIndexProxy extends ModelProxy
{
    public static function modelClass()
    {
        return ProductInventoryIndex::class;
    }
}
