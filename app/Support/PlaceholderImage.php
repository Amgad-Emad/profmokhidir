<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Generates plain grey placeholder images with GD for seeders and tests.
 * Nothing here is brand photography; the images exist so layouts can be checked.
 */
final class PlaceholderImage
{
    /**
     * Write a grey PNG to a temporary file and return its path.
     */
    public static function grey(int $width = 1600, int $height = 1067, ?string $label = null): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('The GD extension is required to generate placeholder images.');
        }

        $image = imagecreatetruecolor($width, $height);
        $grey = imagecolorallocate($image, 0xD3, 0xD1, 0xC5); // stone
        $ink = imagecolorallocate($image, 0x57, 0x5A, 0x55); // ash
        imagefilledrectangle($image, 0, 0, $width, $height, $grey);

        if ($label !== null) {
            imagestring($image, 5, 16, 16, $label, $ink);
        }

        $directory = sys_get_temp_dir().'/profmokhidir-placeholders';

        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Could not create the placeholder image directory.');
        }

        $path = sprintf('%s/placeholder-%dx%d-%s.png', $directory, $width, $height, bin2hex(random_bytes(4)));
        imagepng($image, $path);
        imagedestroy($image);

        return $path;
    }
}
