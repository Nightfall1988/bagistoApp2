<?php

namespace Hitexis\PrintCalculator\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Hitexis\PrintCalculator\Models\ProductPrintDataProxy;
use Hitexis\PrintCalculator\Models\PositionPrintTechniquesProxy;

class PrintingPositions extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_print_data_id',
        'position_id',
        'print_size_unit',
        'max_print_size_height',
        'max_print_size_width',
        'rotation',
        'print_position_type',
    ];

    // Relationship to product print data
    public function productPrintData()
    {
        return $this->belongsTo(ProductPrintDataProxy::modelClass(), 'product_print_data_id');
    }

    // // Relationship to position print techniques
    // public function positionPrintTechniques()
    // {
    //     return $this->hasMany(PositionPrintTechniquesProxy::modelClass(), 'printing_position_id');
    // }

    public function printTechnique()
    {
        return $this->belongsToMany(
            PrintTechniqueProxy::modelClass(),      // The related model
            'position_print_techniques',            // The pivot table
            'printing_position_id',                 // Foreign key on the pivot table for PrintingPositions
            'print_technique_id'                    // Foreign key on the pivot table for PrintTechnique
        );
    }
}
