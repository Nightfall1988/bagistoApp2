<?php

namespace Hitexis\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Hitexis\Product\Models\ProductSupplier;

class ProductSupplierFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProductSupplier::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'product_id'   => $this->faker->numberBetween(1, 10),
            'supplier_code'  => $this->faker->numberBetween(0, 10),
        ];
    }
}
