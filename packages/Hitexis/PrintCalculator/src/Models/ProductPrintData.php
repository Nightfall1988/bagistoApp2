<?php

namespace Hitexis\PrintCalculator\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Hitexis\PrintCalculator\Models\PrintManipulationProxy;
use Hitexis\Product\Models\ProductProxy;

class ProductPrintData extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'print_manipulation_id',
        'print_template',
    ];

    // Define the relationship back to the product
    public function product()
    {
        return $this->belongsTo(ProductProxy::class, 'product_id');
    }

    // Relationship with print manipulations
    public function print_manipulation()
    {
        return $this->belongsTo(PrintManipulationProxy::class, 'print_manipulation_id');
    }
}
