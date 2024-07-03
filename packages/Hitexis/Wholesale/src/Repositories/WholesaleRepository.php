<?php

namespace Hitexis\Wholesale\Repositories;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Eloquent\Repository;
use Hitexis\Wholesale\Contracts\Wholesale as WholesaleContract;

class WholesaleRepository extends Repository implements WholesaleContract
{
    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(
        Container $container
    ) {
        parent::__construct($container);
    }

    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Hitexis\Wholesale\Models\Wholesale';
    }

    /**
     * @return \Hitexis\Wholesale\Contracts\Wholesale
     */
    public function create(array $data)
    {

        $deal = parent::create($data);

        foreach ($data as $key => $value) {
            $deal->$key = $value;
        }

        $deal->products()->attach($deal->product_id);
        
        return $deal;
    }

    /**
     * @param  int  $id
     * @param  string  $attribute
     * @return \Hitexis\Wholesale\Contracts\Wholesale
     */
    public function update(array $data, $id, $attribute = 'id')
    {
        // dd($data, $id, $attribute);
        $wholesale = parent::update($data, $id);

        return $wholesale;

    //     $cartRule = $this->find($id);

    //     parent::update($data, $id, $attribute);

    //     $cartRule->channels()->sync($data['channels']);

    //     $cartRule->customer_groups()->sync($data['customer_groups']);

    //     if ($data['coupon_type']) {
    //         if (! $data['use_auto_generation']) {
    //             $cartRuleCoupon = $this->cartRuleCouponRepository->findOneWhere([
    //                 'is_primary'   => 1,
    //                 'cart_rule_id' => $cartRule->id,
    //             ]);

    //             if ($cartRuleCoupon) {
    //                 $this->cartRuleCouponRepository->update([
    //                     'code'               => $data['coupon_code'],
    //                     'usage_limit'        => $data['uses_per_coupon'] ?? 0,
    //                     'usage_per_customer' => $data['usage_per_customer'] ?? 0,
    //                     'expired_at'         => ! empty($data['ends_till']) ? $data['ends_till'] : null,
    //                 ], $cartRuleCoupon->id);
    //             } else {
    //                 $this->cartRuleCouponRepository->create([
    //                     'cart_rule_id'       => $cartRule->id,
    //                     'code'               => $data['coupon_code'],
    //                     'usage_limit'        => $data['uses_per_coupon'] ?? 0,
    //                     'usage_per_customer' => $data['usage_per_customer'] ?? 0,
    //                     'is_primary'         => 1,
    //                     'expired_at'         => ! empty($data['ends_till']) ? $data['ends_till'] : null,
    //                 ]);
    //             }
    //         } else {
    //             $this->cartRuleCouponRepository->deleteWhere([
    //                 'is_primary'   => 1,
    //                 'cart_rule_id' => $cartRule->id,
    //             ]);

    //             $this->cartRuleCouponRepository->where('cart_rule_id', $cartRule->id)->update([
    //                 'usage_limit'        => $data['uses_per_coupon'] ?? 0,
    //                 'usage_per_customer' => $data['usage_per_customer'] ?? 0,
    //                 'expired_at'         => ! empty($data['ends_till']) ? $data['ends_till'] : null,
    //             ]);
    //         }
    //     } else {
    //         $cartRuleCoupon = $this->cartRuleCouponRepository->deleteWhere(['is_primary' => 1, 'cart_rule_id' => $cartRule->id]);
    //     }

    //     return $cartRule;
    }
}