<?php

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Models\CareLog;
use App\Models\Tree;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CareLog>
 */
class CareLogFactory extends Factory
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
            'activity_type' => fake()->randomElement(ActivityType::cases()),
            'performed_on' => $performedOn = CarbonImmutable::instance(fake()->dateTimeBetween('-1 year')),
            'next_due_on' => fake()->boolean(40) ? $performedOn->addDays(fake()->numberBetween(7, 60)) : null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
