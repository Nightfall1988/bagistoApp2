<?php
namespace Hitexis\Product\Models;

use Konekt\Concord\Proxies\ModelProxy;

class ClientProxy extends ModelProxy
{
    public static function modelClass()
    {
        return Client::class;
    }
}



