<?php
namespace Hitexis\Product\Models;
use Webkul\Product\Models\ProductSalableInventory as WebkulProductSalableInventory;
use Hitexis\Product\Contracts\ProductSalableInventory as ProductSalableInventoryContract;

class ProductSalableInventory extends WebkulProductSalableInventory implements ProductSalableInventoryContract
{
}