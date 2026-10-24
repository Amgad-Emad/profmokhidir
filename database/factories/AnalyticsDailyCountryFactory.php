<?php

namespace Database\Factories;

use App\Models\AnalyticsDailyCountry;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalyticsDailyCountry>
 */
class AnalyticsDailyCountryFactory extends Factory
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
            'country' => fake()->randomElement(['SD', 'SA', 'EG', 'GB', 'US', 'AE', 'QA', 'DE']),
            'visitors' => $visitors = fake()->numberBetween(1, 80),
            'pageviews' => $visitors * fake()->numberBetween(1, 4),
        ];
    }

    public function forDay(CarbonInterface $day): static
    {
        return $this->state(fn (array $attributes) => ['day' => $day->toDateString()]);
    }

    public function country(string $code): static
    {
        return $this->state(fn (array $attributes) => ['country' => $code]);
    }
}
