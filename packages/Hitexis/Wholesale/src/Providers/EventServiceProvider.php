<?php

namespace Webkul\Wholesale\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'promotions.catalog_rule.create.before'  => [
            'Webkul\CatalogRule\Listeners\CatalogRule@afterUpdateCreate',
        ],
    ];
}
