<?php

namespace Hitexis\Wholesale\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Admin\Database\Factories\CatalogRuleFactory;
use Hitexis\Wholesale\Contracts\Wholesale as WholesaleContract;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Customer\Models\CustomerGroupProxy;
use Webkul\Product\Models\ProductProxy;

class Wholesale extends Model implements WholesaleContract
{
    use HasFactory;

    protected $table = 'wholesale';
    /**
     * Add fillable property to the model.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'batch_amount',
        'discount_percentage',
        'status',
        'type',
        'product_id'
    ];

    /**
     * Cast the conditions to the array.
     *
     * @var array
     */
    protected $casts = [
        'conditions' => 'array',
    ];


    /**
     * Get the channels that owns the catalog rule.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(ProductProxy::modelClass(), 'wholesale_product', 'wholesale_id', 'product_id');
    }
    // /**
    //  * Get the customer groups that owns the catalog rule.
    //  */
    // public function customer_groups(): BelongsToMany
    // {
    //     return $this->belongsToMany(CustomerGroupProxy::modelClass(), 'catalog_rule_customer_groups');
    // }

    // /**
    //  * Get the Catalog rule Product that owns the catalog rule
    //  */
    // public function catalog_rule_products(): HasMany
    // {
    //     return $this->hasMany(CatalogRuleProductProxy::modelClass());
    // }

    // /**
    //  * Get the Catalog rule Product that owns the catalog rule.
    //  */
    // public function catalog_rule_product_prices(): HasMany
    // {
    //     return $this->hasMany(CatalogRuleProductPriceProxy::modelClass());
    // }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return WholesaleFactory::new();
    }
}
