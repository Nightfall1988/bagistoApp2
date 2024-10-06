<?php

namespace Hitexis\PrintCalculator\Models;

use Hitexis\PrintCalculator\Contracts\PrintTechnique as PrintTechniqueContract;
use Hitexis\Product\Models\ProductProxy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as BasePrintTechnique;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintTechnique extends BasePrintTechnique implements PrintTechniqueContract
{
    use HasFactory;

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
    public function product(): belongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    public function print_manipulations()
    {
        return $this->belongsToMany(PrintManipulation::class, 'print_technique_manipulation');
    }
}
