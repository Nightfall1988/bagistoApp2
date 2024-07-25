<?php

namespace Hitexis\Marketing\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Hitexis\Marketing\Models\SearchTerm::class,
    ];
}
