<?php

namespace Database\Factories;

use App\Constants\Analytics;
use App\Constants\DeviceType;
use App\Models\AnalyticsEvent;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AnalyticsEvent>
 */
class AnalyticsEventFactory extends Factory
{
    /** Public pages the factory picks from: path => title. */
    public const array PAGES = [
        '/' => 'Home',
        '/about' => 'About Prof. Mokhidir',
        '/cv' => 'CV and Achievements',
        '/publications' => 'Publications',
        '/commentary' => 'Commentary Corner',
        '/tributes' => 'Tributes',
        '/teaching' => 'Teaching Materials',
        '/pictures' => 'Pictures',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $path = fake()->randomElement(array_keys(self::PAGES));

        return [
            'view_id' => (string) Str::uuid(),
            'visitor_hash' => hash('sha256', fake()->unique()->uuid()),
            'occurred_at' => now()->subMinutes(fake()->numberBetween(1, 600)),
            'path' => $path,
            'title' => self::PAGES[$path],
            'section' => Analytics::sectionFor($path),
            'referrer_domain' => fake()->optional(0.5)->randomElement(['google.com', 'scholar.google.com', 'bing.com', 'facebook.com', 'uofk.edu']),
            'country' => fake()->randomElement(['SD', 'SA', 'EG', 'GB', 'US', 'AE', Analytics::UNKNOWN_COUNTRY]),
            'device' => fake()->randomElement(DeviceType::cases()),
            'browser' => fake()->randomElement(['Chrome', 'Safari', 'Firefox', 'Edge']),
            'utm_source' => null,
            'engaged_seconds' => fake()->numberBetween(0, 300),
            'scroll_depth' => fake()->randomElement([0, ...Analytics::SCROLL_DEPTHS]),
        ];
    }

    public function at(CarbonInterface $moment): static
    {
        return $this->state(fn (array $attributes) => ['occurred_at' => $moment]);
    }

    public function forVisitor(string $hash): static
    {
        return $this->state(fn (array $attributes) => ['visitor_hash' => $hash]);
    }

    public function page(string $path, ?string $title = null): static
    {
        return $this->state(fn (array $attributes) => ['path' => $path, 'title' => $title, 'section' => Analytics::sectionFor($path)]);
    }

    public function engaged(int $seconds, int $scrollDepth = 0): static
    {
        return $this->state(fn (array $attributes) => ['engaged_seconds' => $seconds, 'scroll_depth' => $scrollDepth]);
    }

    public function from(?string $referrerDomain): static
    {
        return $this->state(fn (array $attributes) => ['referrer_domain' => $referrerDomain]);
    }

    public function country(string $code): static
    {
        return $this->state(fn (array $attributes) => ['country' => $code]);
    }

    public function device(DeviceType $device, string $browser = 'Chrome'): static
    {
        return $this->state(fn (array $attributes) => ['device' => $device, 'browser' => $browser]);
    }

    public function campaign(string $source): static
    {
        return $this->state(fn (array $attributes) => ['utm_source' => $source]);
    }
}
