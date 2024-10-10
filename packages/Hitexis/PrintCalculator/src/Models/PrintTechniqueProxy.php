<?php
namespace Hitexis\PrintCalculator\Models;

use Konekt\Concord\Proxies\ModelProxy;
use Hitexis\PrintCalculator\Contracts\PrintTechnique as PrintTechniqueContract;

class PrintTechniqueProxy extends ModelProxy implements PrintTechniqueContract
{
    public static function modelClass()
    {
        return PrintTechnique::class;
    }

}