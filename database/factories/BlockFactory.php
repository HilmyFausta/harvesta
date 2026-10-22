<?php

namespace Database\Factories;

use App\Models\Block;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Block>
 */
class BlockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Block '.fake()->unique()->bothify('??-##'),
            'area_sqm' => fake()->optional()->randomFloat(2, 500, 20000),
            'location_note' => fake()->optional()->sentence(4),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
