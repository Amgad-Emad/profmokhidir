<?php

namespace Database\Factories;

use App\Models\OtherItem;
use Database\Factories\Concerns\HasPublishingStates;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OtherItem>
 */
class OtherItemFactory extends Factory
{
    use HasPublishingStates;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = '[Placeholder] '.rtrim(fake()->sentence(5), '.');

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'item_type' => fake()->randomElement(['announcement', 'press', 'interview', 'resource']),
            'summary' => '[Placeholder] '.fake()->sentence(16),
            'body' => '<p>[Placeholder] '.fake()->paragraph(5).'</p>',
            'date' => fake()->dateTimeBetween('-8 years', '-1 week')->format('Y-m-d'),
            'category' => fake()->randomElement(['In the press', 'Archive', 'Announcements']),
            'destination_url' => fake()->boolean(50) ? fake()->url() : null,
            'sort_order' => fake()->numberBetween(1, 50),
        ];
    }

    public function category(string $category): static
    {
        return $this->state(fn (array $attributes) => ['category' => $category]);
    }
}
