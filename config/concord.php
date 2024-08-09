<?php

return [

    'convention' => Webkul\Core\CoreConvention::class,

    'modules' => [

        /**
         * Example:
         * VendorA\ModuleX\Providers\ModuleServiceProvider::class,
         * VendorB\ModuleY\Providers\ModuleServiceProvider::class
         */

        \Webkul\Admin\Providers\ModuleServiceProvider::class,
        \Webkul\Attribute\Providers\ModuleServiceProvider::class,
        \Webkul\CartRule\Providers\ModuleServiceProvider::class,
        \Webkul\CatalogRule\Providers\ModuleServiceProvider::class,
        \Webkul\Category\Providers\ModuleServiceProvider::class,
        \Webkul\Checkout\Providers\ModuleServiceProvider::class,// WEBKUL
        \Hitexis\Checkout\Providers\ModuleServiceProvider::class,// HITEXIS
        \Hitexis\CartRule\Providers\ModuleServiceProvider::class,// HITEXIS
        // Add your custom module provider here
        \Hitexis\Product\Providers\ModuleServiceProvider::class,
        \Webkul\Admin\Providers\ModuleServiceProvider::class,
        \Hitexis\Admin\Providers\ModuleServiceProvider::class,
        \Webkul\Attribute\Providers\ModuleServiceProvider::class,  // WEBKUL
        \Hitexis\Attribute\Providers\ModuleServiceProvider::class, // HITEXIS
        \Webkul\CartRule\Providers\ModuleServiceProvider::class,
        \Webkul\CatalogRule\Providers\ModuleServiceProvider::class,
        \Webkul\Category\Providers\ModuleServiceProvider::class,
        \Webkul\Checkout\Providers\ModuleServiceProvider::class,
        \Webkul\Core\Providers\ModuleServiceProvider::class,
        \Webkul\CMS\Providers\ModuleServiceProvider::class,
        \Webkul\Customer\Providers\ModuleServiceProvider::class,
        \Webkul\DataTransfer\Providers\ModuleServiceProvider::class,
        \Webkul\Inventory\Providers\ModuleServiceProvider::class,
        \Hitexis\Marketing\Providers\ModuleServiceProvider::class,
        \Webkul\Marketing\Providers\ModuleServiceProvider::class,
        \Webkul\Notification\Providers\ModuleServiceProvider::class,
        \Webkul\Payment\Providers\ModuleServiceProvider::class,
        \Webkul\Paypal\Providers\ModuleServiceProvider::class,
        \Hitexis\Product\Providers\ModuleServiceProvider::class,// HITEXIS
        \Webkul\Product\Providers\ModuleServiceProvider::class, // WEBKUL
        \Webkul\Rule\Providers\ModuleServiceProvider::class,
        \Webkul\Sales\Providers\ModuleServiceProvider::class,
        \Webkul\Shipping\Providers\ModuleServiceProvider::class,
        \Hitexis\Shop\Providers\ModuleServiceProvider::class, // HITEXIS
        \Hitexis\Markup\Providers\ModuleServiceProvider::class, // HITEXIS
        \Hitexis\PrintCalculator\Providers\ModuleServiceProvider::class, // HITEXIS
        \Webkul\Shop\Providers\ModuleServiceProvider::class, // WEBKUL
        \Webkul\SocialLogin\Providers\ModuleServiceProvider::class,
        \Webkul\Tax\Providers\ModuleServiceProvider::class,
        \Webkul\Theme\Providers\ModuleServiceProvider::class,
        \Webkul\User\Providers\ModuleServiceProvider::class,
        \Webkul\Sitemap\Providers\ModuleServiceProvider::class,
        \Hitexis\Wholesale\Providers\ModuleServiceProvider::class,
        
    ],
];
