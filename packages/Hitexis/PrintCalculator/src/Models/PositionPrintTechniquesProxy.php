<?php

namespace Hitexis\PrintCalculator\Models;

use Konekt\Concord\Proxies\ModelProxy;
use Hitexis\PrintCalculator\Contracts\PositionPrintTechniques as PositionPrintTechniquesContract;

class PositionPrintTechniquesProxy extends ModelProxy implements PositionPrintTechniquesContract
{
    public static function modelClass()
    {
        return PositionPrintTechniques::class;
    }
}