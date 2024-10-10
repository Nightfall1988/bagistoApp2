<?php

namespace Hitexis\PrintCalculator\Models;

use Konekt\Concord\Proxies\ModelProxy;
use Hitexis\PrintCalculator\Contracts\ProductUrlImages as ProductUrlImagesContract;

class ProductUrlImagesProxy extends ModelProxy implements ProductUrlImagesContract
{
    public static function modelClass()
    {
        return ProductUrlImages::class;
    }

}