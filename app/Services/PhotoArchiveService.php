<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Archive;
use App\Constants\DateFormats;
use App\Constants\MediaProperties;
use App\Constants\WallFormat;
use App\Models\Album;
use App\Support\ResponsiveImage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Hangs the photo archive: each photograph's place on an album's exhibition
 * wall (a frame in its own proportions, never cropped) and the "Through the
 * decades" timeline across every published album.
 *
 * @phpstan-type Hung array{photo: Media, album: Album, format: WallFormat, span: int, featured: bool, decade: int|null, sizes: string}
 */
class PhotoArchiveService
{
    public function __construct(
        private readonly AlbumService $albums,
    ) {}

    /**
     * The album's photographs in order, each with its wall format and column span.
     *
     * @return list<Hung>
     */
    public function wall(Album $album): array
    {
        $hung = [];

        foreach ($album->photos() as $photo) {
            $hung[] = $this->hang($photo, $album);
        }

        return $hung;
    }

    /**
     * Every published photograph grouped by decade, newest decade first and
     * undated photographs last. Each group keeps album order within a decade.
     *
     * @return list<array{decade: int|null, label: string, count: int, photos: list<Hung>}>
     */
    public function decades(): array
    {
        $groups = [];

        foreach ($this->albums->published() as $album) {
            foreach ($album->photos() as $photo) {
                $hung = $this->hang($photo, $album);
                $groups[$hung['decade'] ?? Archive::UNDATED][] = $hung;
            }
        }

        $decades = array_filter(array_keys($groups), 'is_int');
        rsort($decades);

        if (array_key_exists(Archive::UNDATED, $groups)) {
            $decades[] = Archive::UNDATED;
        }

        $result = [];

        foreach ($decades as $key) {
            $photos = $groups[$key];
            usort($photos, fn (array $a, array $b): int => $this->yearOf($a['photo']) <=> $this->yearOf($b['photo']));

            $result[] = [
                'decade' => is_int($key) ? $key : null,
                'label' => Archive::label(is_int($key) ? $key : null),
                'count' => count($photos),
                'photos' => $photos,
            ];
        }

        return $result;
    }

    /**
     * The decade a photograph is filed under: the editor's override, otherwise its year's.
     */
    public function decadeOf(Media $photo): ?int
    {
        $override = $photo->getAttribute(Archive::DECADE);

        if ($override !== null) {
            return (int) $override;
        }

        $year = $this->yearOf($photo);

        return $year === null ? null : Archive::decadeOf($year);
    }

    public function isFeatured(Media $photo): bool
    {
        return (bool) $photo->getAttribute(Archive::FEATURED);
    }

    /**
     * "1969–1984" from the years of an album's photographs, a single year when
     * they agree, or null when none is dated. Figures are set old-style by the view.
     */
    public function yearRange(Album $album): ?string
    {
        $years = [];

        foreach ($album->photos() as $photo) {
            if (($year = $this->yearOf($photo)) !== null) {
                $years[] = $year;
            }
        }

        if ($years === []) {
            return null;
        }

        $from = min($years);
        $to = max($years);

        return $from === $to ? (string) $from : $from.DateFormats::RANGE_SEPARATOR.$to;
    }

    /**
     * @return Hung
     */
    private function hang(Media $photo, Album $album): array
    {
        [$width, $height] = ResponsiveImage::dimensions($photo) ?? [null, null];
        $format = WallFormat::fromDimensions($width, $height);
        $featured = $this->isFeatured($photo);
        $span = $format->span($featured);

        return [
            'photo' => $photo,
            'album' => $album,
            'format' => $format,
            'span' => $span,
            'featured' => $featured,
            'decade' => $this->decadeOf($photo),
            'sizes' => $this->sizes($span),
        ];
    }

    /**
     * The `sizes` attribute for a frame spanning `span` of the wall's columns:
     * full width on phones, its share of the wall above.
     */
    private function sizes(int $span): string
    {
        $share = (int) round($span / Archive::WALL_COLUMNS * 100);
        $max = (int) round(Archive::WALL_MAX_WIDTH * $span / Archive::WALL_COLUMNS);

        return sprintf('(min-width: 1280px) %dpx, (min-width: 640px) %dvw, 100vw', $max, $share);
    }

    private function yearOf(Media $photo): ?int
    {
        $year = (int) $photo->getCustomProperty(MediaProperties::YEAR, 0);

        return $year > 0 ? $year : null;
    }
}
