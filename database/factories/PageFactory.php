<?php

namespace Database\Factories;

use App\Constants\Pages;
use App\Models\Page;
use Database\Factories\Concerns\HasPublishingStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
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
            'key' => fake()->unique()->randomElement(Pages::all()),
            'sort_order' => fake()->numberBetween(1, 15),
        ];
    }

    /**
     * The row of one page (App\Constants\Pages key).
     */
    public function page(string $key): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $key,
            'sort_order' => (int) array_search($key, Pages::all(), true) + 1,
        ]);
    }
}
