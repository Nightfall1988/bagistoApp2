<?php

namespace Hitexis\Product\Models;

use Konekt\Concord\Proxies\ModelProxy;
use Webkul\Product\Models\ProductReview as WebkulProductReview;

class ProductReviewProxy extends ModelProxy
{
    public static function modelClass()
    {
        return ProductReview::class;
    }
}
