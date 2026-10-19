<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Constants\MediaProperties;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Thrown when content is published while one of its images still lacks the
 * alt text, caption, place or year the public site requires.
 */
class ImagesNotReadyException extends RuntimeException
{
    /**
     * @param  array<int, list<string>>  $missing  media id => missing property keys
     */
    public function __construct(public readonly array $missing)
    {
        parent::__construct(self::describe($missing));
    }

    /**
     * @param  array<int, list<string>>  $missing
     */
    public static function describe(array $missing): string
    {
        $count = count($missing);
        $labels = [];

        foreach ($missing as $keys) {
            foreach ($keys as $key) {
                $labels[$key] = self::label($key);
            }
        }

        return sprintf(
            '%d %s still %s %s. Add the details in the media library before publishing.',
            $count,
            $count === 1 ? 'image' : 'images',
            $count === 1 ? 'lacks' : 'lack',
            implode(', ', array_values($labels)),
        );
    }

    public static function label(string $property): string
    {
        return match ($property) {
            MediaProperties::ALT_TEXT => 'alt text',
            MediaProperties::CAPTION => 'a caption',
            MediaProperties::PLACE => 'a place',
            MediaProperties::YEAR => 'a year',
            default => str_replace('_', ' ', $property),
        };
    }

    /**
     * The media ids that are not ready.
     *
     * @return list<int>
     */
    public function mediaIds(): array
    {
        return array_keys($this->missing);
    }

    public static function forMedia(Media $media, array $missing): self
    {
        return new self([$media->getKey() => $missing]);
    }
}
