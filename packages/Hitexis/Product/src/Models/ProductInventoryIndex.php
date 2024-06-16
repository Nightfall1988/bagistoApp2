<?php
namespace Hitexis\Product\Models;

use Hitexis\Product\Contracts\ProductImage as ProductInventoryIndexContract;
use Webkul\Product\Models\ProductImage as WebkulProductInventoryIndex;

class ProductInventoryIndex extends WebkulProductInventoryIndex implements ProductInventoryIndexContract
{
}
