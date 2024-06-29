<?php

namespace Hitexis\CartRule\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Hitexis\CartRule\Models\CartRule::class,
        \Hitexis\CartRule\Models\CartRuleTranslation::class,
        \Hitexis\CartRule\Models\CartRuleCustomer::class,
        \Hitexis\CartRule\Models\CartRuleCoupon::class,
        \Hitexis\CartRule\Models\CartRuleCouponUsage::class,
    ];
}
