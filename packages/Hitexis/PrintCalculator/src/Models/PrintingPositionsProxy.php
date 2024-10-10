<?php

namespace Hitexis\PrintCalculator\Models;

use Konekt\Concord\Proxies\ModelProxy;
use Hitexis\PrintCalculator\Contracts\PrintingPositions as PrintingPositionsContract;

class PrintingPositionsProxy extends ModelProxy implements PrintManipulationContract
{
    public static function modelClass()
    {
        return PrintingPositions::class;
    }
}