<?php
namespace Hitexis\Product\Models;

use Webkul\Product\Models\Product as BaseProduct;
use Hitexis\Wholesale\Models\WholesaleProxy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Hitexis\Product\Contracts\Product as ProductContract;

class Product extends BaseProduct implements ProductContract
{
    /**
     * Get the wholesale options.
     */
    public function wholesales(): BelongsToMany
    {
        return $this->belongsToMany(WholesaleProxy::modelClass(), 'wholesale_product', 'product_id', 'wholesale_id');
    }
}
