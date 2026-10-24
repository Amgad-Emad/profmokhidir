<?php

namespace Database\Factories;

use App\Constants\AnalyticsDimension;
use App\Constants\DeviceType;
use App\Models\AnalyticsDailyTechnology;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalyticsDailyTechnology>
 */
class AnalyticsDailyTechnologyFactory extends Factory
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
            'dimension' => AnalyticsDimension::Device,
            'value' => fake()->randomElement(DeviceType::values()),
            'visitors' => $visitors = fake()->numberBetween(1, 80),
            'pageviews' => $visitors * fake()->numberBetween(1, 4),
        ];
    }

    public function forDay(CarbonInterface $day): static
    {
        return $this->state(fn (array $attributes) => ['day' => $day->toDateString()]);
    }

    public function browser(string $family = 'Chrome'): static
    {
        return $this->state(fn (array $attributes) => ['dimension' => AnalyticsDimension::Browser, 'value' => $family]);
    }

    public function device(DeviceType $device = DeviceType::Mobile): static
    {
        return $this->state(fn (array $attributes) => ['dimension' => AnalyticsDimension::Device, 'value' => $device->value]);
    }
}
