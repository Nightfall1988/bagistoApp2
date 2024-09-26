<?php
namespace Hitexis\PrintCalculator\Models;

use Illuminate\Database\Eloquent\Model as BasePrintTechnique;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Hitexis\PrintCalculator\Contracts\PrintTechnique as PrintTechniqueContract;
use Hitexis\PrintCalculator\Models\PrintManipulation;
use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Hitexis\Product\Models\ProductProxy;

class PrintTechnique extends BasePrintTechnique implements PrintTechniqueContract
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'technique_id',
        'pricing_type',
        'setup',
        'description',
        'setup_repeat',
        'next_colour_cost_indicator',
        'minimum_colors',
        'position_id',
        'range_id',
        'area_from',
        'area_to',
        'minimum_quantity',
        'price',
        'next_price',
        'default',
        'pricing_data',
        'product_id',
        'print_manipulation_id'
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

    public function print_manipulation()
    {
        return $this->belongsTo(PrintManipulation::class, 'print_manipulation_id');
    }    
}