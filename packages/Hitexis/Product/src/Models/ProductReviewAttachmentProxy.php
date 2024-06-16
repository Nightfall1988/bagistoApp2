<?php

namespace Hitexis\Product\Models;

use Konekt\Concord\Proxies\ModelProxy;

class ProductReviewAttachmentProxy extends ModelProxy
{
    public static function modelClass()
    {
        return ProductReviewAttachment::class;
    }
}
