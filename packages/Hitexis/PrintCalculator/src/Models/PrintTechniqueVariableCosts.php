<?php

namespace Hitexis\PrintCalculator\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Hitexis\PrintCalculator\Models\PrintTechniqueProxy;

use Illuminate\Database\Eloquent\Model;
class PrintTechniqueVariableCosts extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'print_technique_id',
        'range_id',
        'area_from',
        'area_to',
        'pricing_data',
    ];

    public function printTechnique()
    {
        return $this->belongsTo(PrintTechniqueProxy::modelClass(), 'print_technique_id');
    }

}
