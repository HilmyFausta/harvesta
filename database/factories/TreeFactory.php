<?php

namespace Database\Factories;

use App\Enums\TreeStatus;
use App\Models\Block;
use App\Models\Tree;
use App\Models\TreeType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tree>
 */
class TreeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('T-####'),
            'tree_type_id' => TreeType::factory(),
            'block_id' => Block::factory(),
            'planted_on' => fake()->dateTimeBetween('-10 years', '-1 month'),
            'status' => fake()->randomElement(TreeStatus::cases()),
            'height_cm' => fake()->optional()->randomFloat(2, 30, 1500),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the tree has the given status.
     */
    public function status(TreeStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    /**
     * Indicate that the tree is dead.
     */
    public function dead(): static
    {
        return $this->status(TreeStatus::Dead);
    }
}
