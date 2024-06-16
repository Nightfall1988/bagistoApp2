<?php

namespace Hitexis\Attribute\Providers;

use Webkul\Attribute\Providers\AttributeServiceProvider as  ServiceProvider;
use Hitexis\Attribute\Repositories\AttributeRepository;
use Hitexis\Attribute\Contracts\Attribute as AttributeContract;

class AttributeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(AttributeContract::class, AttributeRepository::class);
        $this->app->bind(AttributeOption::class, AttributeOptionRepository::class);
    }
}
