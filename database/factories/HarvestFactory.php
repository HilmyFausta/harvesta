<?php

namespace Database\Factories;

use App\Enums\HarvestGrade;
use App\Enums\HarvestUnit;
use App\Models\Harvest;
use App\Models\Tree;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Harvest>
 */
class HarvestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tree_id' => Tree::factory(),
            'user_id' => User::factory(),
            'harvested_on' => fake()->dateTimeBetween('-1 year'),
            'quantity' => fake()->randomFloat(2, 5, 200),
            'unit' => fake()->randomElement(HarvestUnit::cases()),
            'grade' => fake()->randomElement(HarvestGrade::cases()),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
