<?php

namespace Hitexis\Checkout\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Hitexis\Checkout\Models\Cart::class,
        \Hitexis\Checkout\Models\CartAddress::class,
        \Hitexis\Checkout\Models\CartItem::class,
        \Hitexis\Checkout\Models\CartPayment::class,
        \Hitexis\Checkout\Models\CartShippingRate::class,
    ];
}
