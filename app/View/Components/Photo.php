<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Constants\MediaProperties;
use App\Support\ResponsiveImage;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A brand photograph: responsive WebP sources from the media conversions, the
 * treatment class (warm monochrome portrait or evergreen duotone archive), the
 * recorded width/height so the frame is reserved before the image loads, and a
 * place/year caption in the label style. `tone` is the caption ink: "ivory" on
 * evergreen surfaces (brass), "evergreen" on ivory surfaces (bronze). With
 * `lightbox`, the frame becomes a link the gallery lightbox picks up.
 */
class Photo extends Component
{
    public string $src;

    public ?string $srcset;

    public string $alt;

    public string $treatmentClass;

    public ?string $caption;

    public ?string $placeYear;

    public ?int $width = null;

    public ?int $height = null;

    public function __construct(
        public Media $media,
        public string $tone = 'evergreen',
        public string $sizes = '100vw',
        public string $loading = 'lazy',
        public bool $showCaption = false,
        public bool $captioned = true,
        public bool $lightbox = false,
        public ?string $aspect = null,
    ) {
        $this->src = ResponsiveImage::largestUrl($media);
        $this->srcset = ResponsiveImage::srcset($media);
        $this->alt = (string) ($media->getCustomProperty(MediaProperties::ALT_TEXT) ?? '');
        $this->treatmentClass = ResponsiveImage::treatmentClass($media);
        $this->caption = ResponsiveImage::text($media, MediaProperties::CAPTION);
        $this->placeYear = ResponsiveImage::placeYear($media);

        if (($dimensions = ResponsiveImage::dimensions($media)) !== null) {
            [$this->width, $this->height] = $dimensions;
            $this->aspect ??= $this->width.' / '.$this->height;
        }
    }

    public function onDark(): bool
    {
        return $this->tone === 'ivory';
    }

    public function hasCaption(): bool
    {
        return $this->captioned && ($this->placeYear !== null || ($this->showCaption && $this->caption !== null));
    }

    public function render(): View
    {
        return view('components.photo');
    }
}
