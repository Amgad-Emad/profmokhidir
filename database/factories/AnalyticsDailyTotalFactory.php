<?php

namespace Database\Factories;

use App\Models\AnalyticsDailyTotal;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalyticsDailyTotal>
 */
class AnalyticsDailyTotalFactory extends Factory
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
            'visitors' => $visitors = fake()->numberBetween(20, 200),
            'returning_visitors' => (int) round($visitors * fake()->randomFloat(2, 0.05, 0.25)),
            'visits' => $visits = $visitors + fake()->numberBetween(0, (int) ($visitors / 4)),
            'pageviews' => $pageviews = $visits * fake()->numberBetween(1, 4),
            'engaged_seconds' => $pageviews * fake()->numberBetween(20, 120),
        ];
    }

    public function forDay(CarbonInterface $day): static
    {
        return $this->state(fn (array $attributes) => ['day' => $day->toDateString()]);
    }
}
