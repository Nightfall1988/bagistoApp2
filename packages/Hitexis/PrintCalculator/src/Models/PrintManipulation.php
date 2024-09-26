<?php
namespace Hitexis\PrintCalculator\Models;

use Illuminate\Database\Eloquent\Model as BasePrintManipulation;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Hitexis\PrintCalculator\Contracts\PrintManipulation as PrintManipulationContract;
use Hitexis\PrintCalculator\Model\PrintTechnique;
use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class PrintManipulation extends BasePrintManipulation implements PrintManipulationContract
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'currency',
        'pricelist_valid_from',
        'pricelist_valid_until',
        'code',
        'description',
        'price',
    ];

    public function print_techniques()
    {
        return $this->hasMany(PrintTechnique::class, 'print_manipulation_id');
    }    
}