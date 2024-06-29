<?php

namespace Hitexis\CartRule\Repositories;

use Webkul\Core\Eloquent\Repository;

class CartRuleCouponUsageRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Hitexis\CartRule\Contracts\CartRuleCouponUsage';
    }
}
