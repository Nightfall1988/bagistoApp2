<?php

namespace Hitexis\PrintCalculator\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Hitexis\PrintCalculator\Models\PrintManipulationProxy;
use Hitexis\PrintCalculator\Models\PrintingPositionsProxy;
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

// Relationship to product
    public function product()
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'product_id');
    }

    // Relationship to print manipulation
    public function printManipulation()
    {
        return $this->belongsTo(PrintManipulationProxy::modelClass(), 'print_manipulation_id');
    }

    // Relationship to printing positions
    public function printingPositions()
    {
        return $this->hasMany(PrintingPositionsProxy::modelClass(), 'product_print_data_id');
    }
}
