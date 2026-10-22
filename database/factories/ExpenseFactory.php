<?php

namespace Database\Factories;

use App\Enums\ExpenseCategory;
use App\Models\Block;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'block_id' => fake()->boolean(70) ? Block::factory() : null,
            'user_id' => User::factory(),
            'category' => fake()->randomElement(ExpenseCategory::cases()),
            'amount' => fake()->randomFloat(2, 50000, 5000000),
            'spent_on' => fake()->dateTimeBetween('-1 year'),
            'description' => fake()->optional()->sentence(4),
        ];
    }
}
