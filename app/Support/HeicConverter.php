<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Process;
use Imagick;
use RuntimeException;

/**
 * Converts HEIC/HEIF photos (the iPhone default) to JPEG when they are staged
 * in the chat. GD cannot read HEIC, so this uses whichever converter the
 * server has: Imagick built with HEIF support, libheif's heif-convert, or
 * macOS sips. Without one, HEIC uploads are refused with a clear message
 * (browsers that can decode HEIC, such as Safari, convert before uploading).
 */
class HeicConverter
{
    public function isAvailable(): bool
    {
        return $this->imagickSupportsHeic() || $this->binary('heif-convert') !== null || $this->binary('sips') !== null;
    }

    /**
     * Write a JPEG next to the HEIC file and return its path.
     *
     * @throws RuntimeException when no converter is available or the conversion fails
     */
    public function toJpeg(string $path): string
    {
        $target = preg_replace('/\.(heic|heif)$/i', '', $path).'.jpg';

        if ($this->imagickSupportsHeic()) {
            $image = new Imagick($path);
            $image->setImageFormat('jpeg');
            $image->writeImage($target);
            $image->clear();

            return $this->checked($target);
        }

        if (($heif = $this->binary('heif-convert')) !== null) {
            Process::run([$heif, '-q', '90', $path, $target])->throw();

            return $this->checked($target);
        }

        if (($sips = $this->binary('sips')) !== null) {
            Process::run([$sips, '-s', 'format', 'jpeg', $path, '--out', $target])->throw();

            return $this->checked($target);
        }

        throw new RuntimeException('HEIC photos cannot be converted on this server. Export the photo as JPEG and attach it again.');
    }

    private function checked(string $target): string
    {
        clearstatcache(true, $target);

        if (! is_file($target) || filesize($target) === 0) {
            throw new RuntimeException('The HEIC photo could not be converted.');
        }

        return $target;
    }

    private function imagickSupportsHeic(): bool
    {
        return class_exists(Imagick::class) && Imagick::queryFormats('HEIC') !== [];
    }

    private function binary(string $name): ?string
    {
        foreach (['/opt/homebrew/bin/', '/usr/local/bin/', '/usr/bin/'] as $directory) {
            if (is_executable($directory.$name)) {
                return $directory.$name;
            }
        }

        return null;
    }
}
