<?php

namespace Hitexis\PrintCalculator\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Hitexis\PrintCalculator\Models\PrintTechniqueProxy;
use Hitexis\PrintCalculator\Models\PrintingPositionsProxy;
use Illuminate\Database\Eloquent\Model;
class PositionPrintTechniques extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'printing_position_id',
        'print_technique_id',
        'default',
        'max_colours',
    ];

    // Relationship to print technique
    public function printingPosition()
    {
        return $this->belongsTo(PrintingPositionsProxy::modelClass(), 'printing_position_id');
    }

    // Relationship to print technique
    public function printTechnique()
    {
        return $this->belongsTo(PrintTechniqueProxy::modelClass(), 'print_technique_id');
    }
}
