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

        return trans_choice(
            '{1} :count image still lacks :details. Add the details in the media library before publishing.|[2,*] :count images still lack :details. Add the details in the media library before publishing.',
            $count,
            ['details' => implode(__(', '), array_values($labels))],
        );
    }

    public static function label(string $property): string
    {
        return match ($property) {
            MediaProperties::ALT_TEXT => __('alt text'),
            MediaProperties::CAPTION => __('a caption'),
            MediaProperties::PLACE => __('a place'),
            MediaProperties::YEAR => __('a year'),
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
