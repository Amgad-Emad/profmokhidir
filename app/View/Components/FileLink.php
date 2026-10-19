<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Support\FileSize;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A link to a file. The text shows the file type and size ("PDF · 2.1 MB")
 * and, since it is a file, the link opens in a new tab.
 */
class FileLink extends Component
{
    public string $type;

    public string $size;

    public function __construct(
        public Media $media,
        public string $href,
        public string $label = 'Download',
    ) {
        $this->type = self::typeOf($media);
        $this->size = FileSize::format((int) $media->size);
    }

    public static function typeOf(Media $media): string
    {
        $extension = pathinfo((string) $media->file_name, PATHINFO_EXTENSION);

        if ($extension !== '') {
            return strtoupper($extension);
        }

        $mime = (string) $media->mime_type;

        return $mime === '' ? 'File' : strtoupper((string) substr(strrchr($mime, '/') ?: $mime, 1));
    }

    public function text(): string
    {
        return $this->label.' · '.$this->type.' · '.$this->size;
    }

    public function render(): View|Closure|string
    {
        return view('components.file-link');
    }
}
