<?php

namespace Database\Factories;

use App\Models\Award;
use Database\Factories\Concerns\HasPublishingStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Award>
 */
class AwardFactory extends Factory
{
    use HasPublishingStates;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => '[Placeholder] '.fake()->randomElement(['Medal', 'Prize', 'Fellowship', 'Lifetime Achievement Award']).' for '.fake()->word(),
            'organisation' => fake()->company(),
            'year' => fake()->numberBetween(1975, 2023),
            'description' => '[Placeholder] '.fake()->sentence(16),
            'source_url' => fake()->boolean(60) ? fake()->url() : null,
            'sort_order' => fake()->numberBetween(1, 50),
        ];
    }
}
