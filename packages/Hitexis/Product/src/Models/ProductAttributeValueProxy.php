<?php
namespace Hitexis\Product\Models;

use Konekt\Concord\Proxies\ModelProxy;

class ProductAttributeValueProxy extends ModelProxy
{
    public static function modelClass()
    {
        return ProductAttributeValue::class;
    }
}



