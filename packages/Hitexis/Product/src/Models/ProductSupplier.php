<?php

namespace Hitexis\Product\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Hitexis\Product\Contracts\ProductSupplier as ProductSupplierContract;
use Hitexis\Product\Database\Factories\ProductSupplierFactory;

class ProductSupplier extends Model implements ProductSupplierContract
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'supplier_code'
    ];

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }


    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return ProductSupplierFactory::new();
    }
}
