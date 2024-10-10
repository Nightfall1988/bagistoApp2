<?php

namespace Hitexis\PrintCalculator\Models;

use Hitexis\PrintCalculator\Contracts\PrintTechnique as PrintTechniqueContract;
use Hitexis\PrintCalculator\Models\PrintManipulationProxy;
use Hitexis\Product\Models\ProductProxy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as BasePrintTechnique;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintTechnique extends BasePrintTechnique implements PrintTechniqueContract
{
    use HasFactory;

    protected $primaryKey = 'technique_id';  // Set primary key to technique_id
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'technique_id',
        'description',
        'pricing_type',
        'setup',
        'setup_repeat',
        'next_colour_cost_indicator',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'additional' => 'array',
    ];

    /**
     * Get the wholesale options.
     */
    public function products()
    {
        return $this->belongsToMany(ProductProxy::modelClass(), 'position_print_techniques', 'print_technique_id', 'printing_position_id');
    }

}
