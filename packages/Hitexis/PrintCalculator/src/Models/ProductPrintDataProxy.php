<?php

namespace Hitexis\PrintCalculator\Models;

use Konekt\Concord\Proxies\ModelProxy;
use Hitexis\PrintCalculator\Contracts\ProductPrintData as ProductPrintDataContract;

class ProductPrintDataProxy extends ModelProxy implements ProductPrintDataContract
{
    public static function modelClass()
    {
        return ProductPrintData::class;
    }
}