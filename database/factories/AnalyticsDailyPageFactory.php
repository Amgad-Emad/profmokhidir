<?php

namespace Database\Factories;

use App\Constants\Analytics;
use App\Models\AnalyticsDailyPage;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalyticsDailyPage>
 */
class AnalyticsDailyPageFactory extends Factory
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
            'path' => $path = fake()->randomElement(array_keys(AnalyticsEventFactory::PAGES)),
            'title' => AnalyticsEventFactory::PAGES[$path],
            'section' => Analytics::sectionFor($path),
            'views' => $views = fake()->numberBetween(1, 120),
            'visitors' => max(1, $views - fake()->numberBetween(0, (int) ($views / 3))),
            'engaged_seconds' => $views * fake()->numberBetween(10, 200),
            'scroll_depth_total' => $views * fake()->randomElement(Analytics::SCROLL_DEPTHS),
        ];
    }

    public function forDay(CarbonInterface $day): static
    {
        return $this->state(fn (array $attributes) => ['day' => $day->toDateString()]);
    }

    public function page(string $path, ?string $title = null): static
    {
        return $this->state(fn (array $attributes) => ['path' => $path, 'title' => $title, 'section' => Analytics::sectionFor($path)]);
    }
}
