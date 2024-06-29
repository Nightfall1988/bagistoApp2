<?php

namespace App\Observers;

use Hitexis\Product\Models\Product;
use Hitexis\Wholesale\Models\Wholesale;

class WholesaleObserver
{
    public function created(Wholesale $wholesale)
    {
        $products = Product::all();

        foreach ($products as $product) {
            $product->wholesales()->attach($wholesale->id);
        }
    }

    /**
     * Handle the Wholesale "updated" event.
     */
    public function updated(Wholesale $wholesale): void
    {
        //
    }

    /**
     * Handle the Wholesale "deleted" event.
     */
    public function deleted(Wholesale $wholesale): void
    {
        //
    }

    /**
     * Handle the Wholesale "restored" event.
     */
    public function restored(Wholesale $wholesale): void
    {
        //
    }

    /**
     * Handle the Wholesale "force deleted" event.
     */
    public function forceDeleted(Wholesale $wholesale): void
    {
        //
    }
}
