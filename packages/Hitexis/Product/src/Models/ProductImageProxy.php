<?php
namespace Hitexis\Product\Models;

use Konekt\Concord\Proxies\ModelProxy;

class ProductImageProxy extends ModelProxy
{
    public static function modelClass()
    {
        return ProductImage::class;
    }
}
