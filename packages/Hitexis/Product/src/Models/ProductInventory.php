<?php

namespace Hitexis\Product\Models;
use Webkul\Product\Models\ProductInventory as WebkulProductInventory;
use Hitexis\Product\Contracts\ProductInventory as ProductInventoryContract;

class ProductInventory extends WebkulProductInventory implements ProductInventoryContract
{
}