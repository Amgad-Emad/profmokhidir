<?php

namespace Database\Factories;

use App\Constants\TeachingAccess;
use App\Constants\TeachingMaterialType;
use App\Models\TeachingMaterial;
use Database\Factories\Concerns\HasPublishingStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeachingMaterial>
 */
class TeachingMaterialFactory extends Factory
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
            'title' => '[Placeholder] '.rtrim(fake()->sentence(4), '.'),
            'course' => fake()->randomElement(['AGR 301 Crop Physiology', 'AGR 412 Soil Fertility', 'AGR 205 Principles of Agronomy']),
            'course_description' => null,
            'topic' => fake()->randomElement(['Photosynthesis', 'Nitrogen cycle', 'Irrigation', 'Seed systems']),
            'type' => fake()->randomElement(TeachingMaterialType::cases()),
            'description' => '[Placeholder] '.fake()->sentence(14),
            'version' => 'v'.fake()->numberBetween(1, 4).'.'.fake()->numberBetween(0, 9),
            'material_date' => fake()->dateTimeBetween('-15 years', '-1 month')->format('Y-m-d'),
            'external_url' => fake()->boolean(40) ? fake()->url() : null,
            'access' => TeachingAccess::Public,
            'sort_order' => fake()->numberBetween(1, 50),
        ];
    }

    public function membersOnly(): static
    {
        return $this->state(fn (array $attributes) => ['access' => TeachingAccess::Members]);
    }

    public function type(TeachingMaterialType $type): static
    {
        return $this->state(fn (array $attributes) => ['type' => $type]);
    }

    public function course(string $course): static
    {
        return $this->state(fn (array $attributes) => ['course' => $course]);
    }
}
