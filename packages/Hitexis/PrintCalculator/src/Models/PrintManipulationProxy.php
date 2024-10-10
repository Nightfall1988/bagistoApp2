<?php

namespace Hitexis\PrintCalculator\Models;

use Konekt\Concord\Proxies\ModelProxy;
use Hitexis\PrintCalculator\Contracts\PrintManipulation as PrintManipulationContract;

class PrintManipulationProxy extends ModelProxy implements PrintManipulationContract
{
    public static function modelClass()
    {
        return PrintManipulation::class;
    }
}