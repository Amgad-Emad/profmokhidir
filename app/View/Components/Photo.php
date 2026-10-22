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
 * museum-label caption (title, place and year). `tone` is the caption ink: "ivory" on
 * evergreen surfaces (brass), "evergreen" on ivory surfaces (bronze). With
 * `lightbox`, the frame becomes a link the gallery lightbox picks up. With
 * `label`, the caption is set as a museum label (<x-museum-label>); with
 * `lift`, an archive photo's duotone lifts gently toward the original on hover.
 */
class Photo extends Component
{
    public string $src;

    public ?string $srcset;

    public string $alt;

    public string $treatmentClass;

    public ?string $caption;

    public ?string $placeYear;

    public ?string $note;

    public ?string $credit;

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
        public bool $label = true,
        public bool $lift = false,
    ) {
        $this->src = ResponsiveImage::largestUrl($media);
        $this->srcset = ResponsiveImage::srcset($media);
        $this->alt = (string) ($media->getCustomProperty(MediaProperties::ALT_TEXT) ?? '');
        $this->treatmentClass = ResponsiveImage::treatmentClass($media);
        $this->caption = ResponsiveImage::text($media, MediaProperties::CAPTION);
        $this->placeYear = ResponsiveImage::placeYear($media);
        $this->note = ResponsiveImage::text($media, MediaProperties::NOTE);
        $this->credit = ResponsiveImage::text($media, MediaProperties::CREDIT);

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

    /**
     * The duotone lifts toward the original on hover: an untreated copy sits
     * beneath the treated image, which fades a little. Archive photos only.
     */
    public function lifts(): bool
    {
        return $this->lift && $this->treatmentClass === ResponsiveImage::CLASS_ARCHIVE;
    }

    public function render(): View
    {
        return view('components.photo');
    }
}
