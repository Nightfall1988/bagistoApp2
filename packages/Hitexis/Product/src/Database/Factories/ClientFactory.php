<?php

namespace Hitexis\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Hitexis\Product\Models\Client;

class ClientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Client::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name'        => $this->faker->numberBetween(1, 10),
            'image_path'  => $this->faker->numberBetween(0, 10),
        ];
    }
}
