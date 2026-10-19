<?php

namespace Database\Factories;

use App\Constants\CvSection;
use App\Models\CvEntry;
use Database\Factories\Concerns\HasPublishingStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CvEntry>
 */
class CvEntryFactory extends Factory
{
    use HasPublishingStates;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->numberBetween(1962, 2015);

        return [
            'section' => fake()->randomElement(CvSection::cases()),
            'title' => '[Placeholder] '.fake()->jobTitle(),
            'organisation' => fake()->company(),
            'role_description' => '[Placeholder] '.fake()->sentence(14),
            'start_year' => $start,
            'end_year' => fake()->boolean(70) ? min($start + fake()->numberBetween(1, 12), 2024) : null,
            'last_updated_at' => now()->subDays(fake()->numberBetween(1, 365))->toDateString(),
            'sort_order' => fake()->numberBetween(1, 50),
        ];
    }

    public function section(CvSection $section): static
    {
        return $this->state(fn (array $attributes) => ['section' => $section]);
    }

    public function current(): static
    {
        return $this->state(fn (array $attributes) => ['end_year' => null]);
    }
}
