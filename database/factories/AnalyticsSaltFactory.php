<?php

namespace Database\Factories;

use App\Constants\Analytics;
use App\Models\AnalyticsSalt;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalyticsSalt>
 */
class AnalyticsSaltFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'day' => now()->toDateString(),
            'salt' => bin2hex(random_bytes(Analytics::SALT_BYTES)),
        ];
    }

    public function forDay(CarbonInterface $day): static
    {
        return $this->state(fn (array $attributes) => ['day' => $day->toDateString()]);
    }

    /**
     * Yesterday's salt (one the next page view must delete).
     */
    public function yesterday(): static
    {
        return $this->forDay(now()->subDay());
    }
}
