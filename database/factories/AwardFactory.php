<?php

namespace Database\Factories;

use App\Constants\MediaProperties;
use App\Models\Award;
use App\Services\AwardService;
use App\Support\PlaceholderImage;
use Database\Factories\Concerns\HasPublishingStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Award>
 */
class AwardFactory extends Factory
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
            'name' => '[Placeholder] '.fake()->randomElement(['Medal', 'Prize', 'Fellowship', 'Lifetime Achievement Award']).' for '.fake()->word(),
            'organisation' => fake()->company(),
            'year' => fake()->numberBetween(1975, 2023),
            'description' => '[Placeholder] '.fake()->sentence(16),
            'source_url' => fake()->boolean(60) ? fake()->url() : null,
            'sort_order' => fake()->numberBetween(1, 50),
        ];
    }

    /**
     * Photographs of the certificate, medal or shield, with every detail the public site needs.
     */
    public function withImages(int $count = 1, int $width = 640, int $height = 480): static
    {
        return $this->afterCreating(function (Award $award) use ($count, $width, $height): void {
            $service = app(AwardService::class);

            for ($i = 1; $i <= $count; $i++) {
                $service->addImage($award, PlaceholderImage::grey($width, $height, "Award {$i}"), [
                    MediaProperties::ALT_TEXT => "Placeholder photograph {$i} of {$award->name}",
                    MediaProperties::CAPTION => "[Placeholder] Certificate {$i}",
                    MediaProperties::PLACE => fake()->city(),
                    MediaProperties::YEAR => $award->year ?? fake()->numberBetween(1975, 2023),
                ]);
            }
        });
    }
}
