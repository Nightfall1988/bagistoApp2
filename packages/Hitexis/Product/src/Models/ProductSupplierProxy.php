<?php
namespace Hitexis\Product\Models;

use Konekt\Concord\Proxies\ModelProxy;

class ProductSupplierProxy extends ModelProxy
{
    public static function modelClass()
    {
        return ProductSupplier::class;
    }
}
