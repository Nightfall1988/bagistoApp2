<?php

namespace Hitexis\Attribute\Providers;

use Webkul\Attribute\Providers\ModuleServiceProvider as CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Hitexis\Attribute\Models\Attribute::class,
        // \Webkul\Attribute\Models\AttributeFamily::class,
        // \Webkul\Attribute\Models\AttributeGroup::class,
        \Hitexis\Attribute\Models\AttributeOption::class,
        // \Webkul\Attribute\Models\AttributeOptionTranslation::class,
        // \Webkul\Attribute\Models\AttributeTranslation::class,
    ];
}
