<?php

namespace Hitexis\Admin\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'customer.registration.after' => [
            'Hitexis\Admin\Listeners\Customer@afterCreated',
        ],

        'admin.password.update.after' => [
            'Hitexis\Admin\Listeners\Admin@afterPasswordUpdated',
        ],

        'checkout.order.save.after' => [
            'Hitexis\Admin\Listeners\Order@afterCreated',
        ],

        'sales.order.cancel.after' => [
            'Hitexis\Admin\Listeners\Order@afterCanceled',
        ],

        'sales.invoice.save.after' => [
            'Hitexis\Admin\Listeners\Invoice@afterCreated',
        ],

        'sales.shipment.save.after' => [
            'Hitexis\Admin\Listeners\Shipment@afterCreated',
        ],

        'sales.refund.save.after' => [
            'Hitexis\Admin\Listeners\Refund@afterCreated',
        ],

        'core.channel.update.after' => [
            'Hitexis\Admin\Listeners\ChannelSettingsChange@checkForMaintenanceMode',
        ],
    ];
}
