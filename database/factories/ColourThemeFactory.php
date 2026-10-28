<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Constants\ThemePresets;
use App\Models\ColourTheme;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * A custom colour theme: the Oxford Navy & Parchment Basic colours unless a
 * state says otherwise. States: active(), preset(), withDraft(), failing()
 * (light body text that fails the contrast guard) and brand().
 *
 * @extends Factory<ColourTheme>
 */
class ColourThemeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Theme '.Str::title($this->faker->unique()->word());
        $preset = ThemePresets::get(ThemePresets::OXFORD);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(1, 99999),
            'is_active' => false,
            'is_preset' => false,
            'based_on' => ThemePresets::OXFORD,
            'light' => $preset['light'],
            'dark' => $preset['dark'],
            'draft_light' => null,
            'draft_dark' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['is_active' => true]);
    }

    /**
     * One of the suggested templates, stored as it is defined.
     */
    public function preset(string $slug = ThemePresets::BURGUNDY): static
    {
        $preset = ThemePresets::get($slug);

        return $this->state(fn (): array => ['name' => $preset['name'], 'slug' => $slug, 'is_preset' => true, 'based_on' => null, 'light' => $preset['light'], 'dark' => $preset['dark']]);
    }

    public function brand(): static
    {
        return $this->preset(ThemePresets::BRAND);
    }

    /**
     * Unpublished edits: a warmer light background.
     */
    public function withDraft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'draft_light' => [...$attributes['light'], 'surface' => '#f6efe0'],
            'draft_dark' => $attributes['dark'],
        ]);
    }

    /**
     * Light body text the colour of its background's neighbour: fails the guard and blocks publishing.
     */
    public function failing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'light' => [...$attributes['light'], 'ink' => '#c9c2b0', 'ink-body' => '#c9c2b0', 'link' => '#c9c2b0'],
        ]);
    }
}
