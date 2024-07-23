<?php

namespace Hitexis\Product\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Hitexis\Product\Contracts\Client as ClientContract;
use Hitexis\Product\Database\Factories\ProductSupplierFactory;

class Client extends Model implements ClientContract
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image_path'
    ];
    
    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return ProductSupplierFactory::new();
    }
}
