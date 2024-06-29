<?php

namespace Hitexis\Checkout\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Hitexis\Checkout\Contracts\CartPayment as CartPaymentContract;
use Hitexis\Checkout\Database\Factories\CartPaymentFactory;

class CartPayment extends Model implements CartPaymentContract
{
    use HasFactory;

    protected $table = 'cart_payment';

    /**
     * Create a new factory instance for the model
     */
    protected static function newFactory(): Factory
    {
        return CartPaymentFactory::new();
    }
}
