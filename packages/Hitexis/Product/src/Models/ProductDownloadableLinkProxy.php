<?php

namespace Hitexis\Product\Models;

use Konekt\Concord\Proxies\ModelProxy;

class ProductDownloadableLinkProxy extends ModelProxy
{
    public static function modelClass()
    {
        return ProductDownloadableLink::class;
    }
}
