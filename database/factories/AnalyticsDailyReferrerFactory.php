<?php

namespace Database\Factories;

use App\Models\AnalyticsDailyReferrer;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalyticsDailyReferrer>
 */
class AnalyticsDailyReferrerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'day' => now()->subDay()->toDateString(),
            'referrer_domain' => fake()->randomElement(['', 'google.com', 'scholar.google.com', 'bing.com', 'facebook.com']),
            'visitors' => $visitors = fake()->numberBetween(1, 80),
            'pageviews' => $visitors * fake()->numberBetween(1, 4),
        ];
    }

    public function forDay(CarbonInterface $day): static
    {
        return $this->state(fn (array $attributes) => ['day' => $day->toDateString()]);
    }

    /**
     * Visits with no referrer.
     */
    public function direct(): static
    {
        return $this->state(fn (array $attributes) => ['referrer_domain' => '']);
    }
}
