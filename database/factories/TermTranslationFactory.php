<?php

namespace Database\Factories;

use App\Constants\Locales;
use App\Constants\TermVocabulary;
use App\Models\TermTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TermTranslation>
 */
class TermTranslationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vocabulary' => fake()->randomElement(TermVocabulary::cases()),
            'term' => fake()->unique()->words(2, true),
            'locale' => Locales::AR,
            'label' => 'مصطلح '.fake()->unique()->numberBetween(1, 99999),
        ];
    }

    public function naming(TermVocabulary $vocabulary, string $term, string $label): static
    {
        return $this->state(['vocabulary' => $vocabulary, 'term' => $term, 'label' => $label]);
    }
}
