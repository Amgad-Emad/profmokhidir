<?php

namespace Database\Factories;

use App\Constants\PublicationType;
use App\Models\Publication;
use Database\Factories\Concerns\HasPublishingStates;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Publication>
 */
class PublicationFactory extends Factory
{
    use HasPublishingStates;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = '[Placeholder] '.rtrim(fake()->sentence(7), '.');

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'type' => fake()->randomElement(PublicationType::cases()),
            'authors' => 'Khidir, M. O.; '.fake()->lastName().', '.Str::upper(fake()->randomLetter()).'.',
            'year' => fake()->numberBetween(1968, 2022),
            'venue_publisher' => fake()->company().' Press',
            'doi' => '10.1000/'.fake()->unique()->numerify('placeholder.#####'),
            'citation' => null,
            'abstract' => '[Placeholder] '.fake()->paragraph(4),
            'url' => fake()->boolean(50) ? fake()->url() : null,
            'is_featured' => false,
            'topic' => fake()->randomElement(['Agronomy', 'Crop physiology', 'Soil fertility', 'Agricultural education']),
            'sort_order' => fake()->numberBetween(1, 50),
        ];
    }

    public function type(PublicationType $type): static
    {
        return $this->state(fn (array $attributes) => ['type' => $type]);
    }

    /**
     * Marked for the "Selected works" strip.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => ['is_featured' => true]);
    }

    public function topic(?string $topic): static
    {
        return $this->state(fn (array $attributes) => ['topic' => $topic]);
    }

    public function year(int $year): static
    {
        return $this->state(fn (array $attributes) => ['year' => $year]);
    }
}
