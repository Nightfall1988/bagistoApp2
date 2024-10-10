<?php

namespace Hitexis\PrintCalculator\Models;

use Konekt\Concord\Proxies\ModelProxy;
use Hitexis\PrintCalculator\Contracts\PrintTechniqueVariableCosts as PrintTechniqueVariableCostsContract;

class PrintTechniqueVariableCostsProxy extends ModelProxy implements PrintTechniqueVariableCostsContract
{
    public static function modelClass()
    {
        return PrintTechniqueVariableCosts::class;
    }
}