<?php

namespace Hitexis\Markup\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Hitexis\Markup\Contracts\Markup as MarkupContract;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Customer\Models\CustomerGroupProxy;
use Webkul\Product\Models\ProductProxy;

class Markup extends Model implements MarkupContract
{
    use HasFactory;

    protected $table = 'markup';
    /**
     * Add fillable property to the model.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'amount',
        'percentage',
        'markup_unit',
        'currency',
        'markup_type',
        'created_at',
        'updated_at',
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
    public function products(): hasMany
    {
        return $this->hasMany(ProductProxy::modelClass(), 'markup_product', 'markup_id', 'product_id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return MarkupFactory::new();
    }
}
