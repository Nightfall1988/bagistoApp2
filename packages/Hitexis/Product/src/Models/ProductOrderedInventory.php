<?php
namespace Hitexis\Product\Models;

use Hitexis\Product\Contracts\ProductOrderedInventory as ProductOrderedInventoryContract;
use Webkul\Product\Models\ProductOrderedInventory as WebkulProductOrderedInventory;

class ProductOrderedInventory extends WebkulProductOrderedInventory implements ProductOrderedInventoryContract
{
}
