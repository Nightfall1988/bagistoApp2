<?php

namespace Hitexis\Product\Models;

use Konekt\Concord\Proxies\ModelProxy;

class ProductDownloadableSampleProxy extends ModelProxy
{
    public static function modelClass()
    {
        return ProductDownloadableSample::class;
    }
}
