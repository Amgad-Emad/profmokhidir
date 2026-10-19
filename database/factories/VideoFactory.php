<?php

namespace Database\Factories;

use App\Constants\MediaProperties;
use App\Constants\VideoProvider;
use App\Models\Video;
use App\Services\VideoService;
use App\Support\PlaceholderImage;
use Database\Factories\Concerns\HasPublishingStates;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Video>
 */
class VideoFactory extends Factory
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
        $provider = fake()->randomElement([VideoProvider::YouTube, VideoProvider::Vimeo, VideoProvider::Link]);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'date' => fake()->dateTimeBetween('-20 years', '-1 month')->format('Y-m-d'),
            'event' => '[Placeholder] '.fake()->catchPhrase().' Lecture',
            'location' => fake()->city(),
            'description' => '[Placeholder] '.fake()->paragraph(3),
            'provider' => $provider,
            'media_url' => match ($provider) {
                VideoProvider::YouTube => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                VideoProvider::Vimeo => 'https://vimeo.com/76979871',
                default => fake()->url(),
            },
            'captions_url' => null,
            'sort_order' => fake()->numberBetween(1, 50),
        ];
    }

    public function provider(VideoProvider $provider, ?string $url = null): static
    {
        return $this->state(fn (array $attributes) => array_filter([
            'provider' => $provider,
            'media_url' => $url,
        ], fn ($value): bool => $value !== null));
    }

    public function withThumbnail(): static
    {
        return $this->afterCreating(function (Video $video): void {
            app(VideoService::class)->setThumbnail($video, PlaceholderImage::grey(960, 540, 'Video'), [
                MediaProperties::ALT_TEXT => 'Placeholder still from '.$video->title,
                MediaProperties::CAPTION => '[Placeholder] '.$video->event,
                MediaProperties::PLACE => $video->location,
                MediaProperties::YEAR => (int) $video->date?->format('Y'),
            ]);
        });
    }
}
