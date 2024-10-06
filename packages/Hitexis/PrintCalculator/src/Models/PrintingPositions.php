<?php

namespace Hitexis\PrintCalculator\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
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

}
