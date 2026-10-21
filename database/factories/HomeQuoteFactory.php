<?php

namespace Database\Factories;

use App\Models\HomeQuote;
use Database\Factories\Concerns\HasPublishingStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomeQuote>
 */
class HomeQuoteFactory extends Factory
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
            'quote' => '[Placeholder] '.fake()->sentence(10),
            'attribution' => fake()->boolean(70) ? '[Placeholder] '.fake()->name() : null,
            'source' => fake()->boolean(50) ? '[Placeholder] '.fake()->words(3, true) : null,
            'sort_order' => fake()->numberBetween(1, 50),
        ];
    }
}
