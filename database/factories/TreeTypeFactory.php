<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\TreeType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TreeType>
 */
class TreeTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => ucfirst(fake()->unique()->word()),
            'scientific_name' => fake()->optional()->words(2, true),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
