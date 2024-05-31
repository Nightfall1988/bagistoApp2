<?php

namespace Hitexis\Wholesale\Providers;

use Konekt\Concord\BaseModuleServiceProvider;
use Illuminate\Support\Facades\Log;
use Hitexis\Wholesale\Models\Wholesale;
use Hitexis\Wholesale\Models\WholesaleProxy;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        Wholesale::class
    ];

    public function boot()
    {
        parent::boot();
        Log::info('Minimal ModuleServiceProvider boot method called.');
    }

    public function register()
    {
        $this->app->bind(Hitexis\Wholesale\Contracts\Wholesale::class, Hitexis\Wholesale\Repositories\WholesaleRepository::class);
    }
}