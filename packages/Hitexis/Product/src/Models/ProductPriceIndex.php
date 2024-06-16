<?php
namespace Hitexis\Product\Models;

use Hitexis\Product\Contracts\ProductPriceIndex as ProductPriceIndexContract;
use Webkul\Product\Models\ProductPriceIndex as WebkulProductPriceIndex;

class ProductPriceIndex extends WebkulProductPriceIndex implements ProductPriceIndexContract
{
}
