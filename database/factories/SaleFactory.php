<?php

namespace Database\Factories;

use App\Models\Harvest;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'harvest_id' => Harvest::factory()->state(['quantity' => 100]),
            'user_id' => User::factory(),
            'buyer_name' => fake()->company(),
            'sold_on' => fake()->dateTimeBetween('-1 year'),
            'quantity' => fake()->randomFloat(2, 1, 50),
            'unit_price' => fake()->randomFloat(2, 5000, 80000),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
