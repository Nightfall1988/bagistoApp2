<?php

namespace Hitexis\CartRule\Listeners;

use Hitexis\CartRule\Helpers\CartRule;

class Cart
{
    /**
     * Create a new listener instance.
     *
     * @param  \Hitexis\CartRule\Repositories\CartRule  $cartRuleHelper
     * @return void
     */
    public function __construct(protected CartRule $cartRuleHelper)
    {
    }

    /**
     * Apply valid cart rules to cart
     *
     * @param  \Hitexis\Checkout\Contracts\Cart  $cart
     * @return void
     */
    public function applyCartRules($cart)
    {
        $this->cartRuleHelper->collect($cart);
    }
}
