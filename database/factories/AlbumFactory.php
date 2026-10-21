<?php

namespace Database\Factories;

use App\Constants\MediaProperties;
use App\Models\Album;
use App\Services\AlbumService;
use App\Support\PlaceholderImage;
use Database\Factories\Concerns\HasPublishingStates;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Album>
 */
class AlbumFactory extends Factory
{
    use HasPublishingStates;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = '[Placeholder] '.Str::title(fake()->words(3, true));

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'description' => '[Placeholder] '.fake()->sentence(12),
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }

    /**
     * Hang a varied wall: one placeholder photo per [width, height, year] in
     * order, the first featured, so the exhibition layout has every format.
     *
     * @param  list<array{int, int, int}>  $prints
     */
    public function withWall(array $prints, string $place = 'Khartoum'): static
    {
        return $this->afterCreating(function (Album $album) use ($prints, $place): void {
            $service = app(AlbumService::class);

            foreach ($prints as $i => [$width, $height, $year]) {
                $photo = $service->addPhoto($album, PlaceholderImage::grey($width, $height, 'Photo '.($i + 1)), [
                    MediaProperties::ALT_TEXT => 'Placeholder photo '.($i + 1).' from '.$album->title,
                    MediaProperties::CAPTION => '[Placeholder] Photo '.($i + 1),
                    MediaProperties::PLACE => $place,
                    MediaProperties::YEAR => $year,
                    MediaProperties::CREDIT => '[Placeholder] Family archive',
                ]);

                if ($i === 0) {
                    $service->updatePhotoArchive($album, $photo, null, true);
                }
            }
        });
    }

    /**
     * Attach grey placeholder photos through the service, in order.
     */
    public function withPhotos(int $count = 3, int $width = 640, int $height = 427): static
    {
        return $this->afterCreating(function (Album $album) use ($count, $width, $height): void {
            $service = app(AlbumService::class);

            for ($i = 1; $i <= $count; $i++) {
                $service->addPhoto($album, PlaceholderImage::grey($width, $height, "Photo {$i}"), [
                    MediaProperties::ALT_TEXT => "Placeholder photo {$i} from {$album->title}",
                    MediaProperties::CAPTION => "[Placeholder] Photo {$i}",
                    MediaProperties::PLACE => fake()->city(),
                    MediaProperties::YEAR => fake()->numberBetween(1965, 2015),
                    MediaProperties::CREDIT => '[Placeholder] Family archive',
                ]);
            }
        });
    }
}
