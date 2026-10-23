<?php

declare(strict_types=1);

namespace App\Support;

use App\Constants\ChatAttachments;
use GdImage;
use RuntimeException;

/**
 * Strips every piece of metadata (EXIF, including GPS location, XMP, comments)
 * from a chat image by decoding and re-encoding its pixels with GD. The EXIF
 * orientation is applied first, so a portrait photo taken on a phone stays
 * upright once its orientation tag is gone.
 */
final class ChatImageSanitiser
{
    /**
     * Rewrite the image at $path in place.
     *
     * @throws RuntimeException when the file is not a readable image
     */
    public function clean(string $path, string $extension): void
    {
        $extension = strtolower($extension);
        $image = $this->load($path, $extension);

        if ($extension === 'jpg' || $extension === 'jpeg') {
            $image = $this->orient($image, $path);
        }

        $written = match ($extension) {
            'png' => $this->writePng($image, $path),
            'webp' => imagewebp($image, $path, ChatAttachments::IMAGE_QUALITY),
            default => imagejpeg($image, $path, ChatAttachments::IMAGE_QUALITY),
        };

        imagedestroy($image);

        if (! $written) {
            throw new RuntimeException('The image could not be processed.');
        }

        clearstatcache(true, $path);
    }

    private function load(string $path, string $extension): GdImage
    {
        $image = match ($extension) {
            'png' => @imagecreatefrompng($path),
            'webp' => @imagecreatefromwebp($path),
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            default => false,
        };

        if (! $image instanceof GdImage) {
            throw new RuntimeException('The image could not be read.');
        }

        return $image;
    }

    private function orient(GdImage $image, string $path): GdImage
    {
        $orientation = function_exists('exif_read_data') ? (int) (@exif_read_data($path)['Orientation'] ?? 1) : 1;

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        if ($rotated instanceof GdImage && $rotated !== $image) {
            imagedestroy($image);
            $image = $rotated;
        }

        if (in_array($orientation, [2, 4, 5, 7], true)) {
            // Mirrored variants: flip, then rotate as above for 5 and 7.
            imageflip($image, $orientation === 4 ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL);

            if ($orientation === 5 || $orientation === 7) {
                $turned = imagerotate($image, $orientation === 5 ? 90 : -90, 0);
                if ($turned instanceof GdImage) {
                    imagedestroy($image);
                    $image = $turned;
                }
            }
        }

        return $image;
    }

    private function writePng(GdImage $image, string $path): bool
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return imagepng($image, $path, 6);
    }
}
