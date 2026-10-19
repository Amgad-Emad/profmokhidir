<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Constants\MediaConversions;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A brand photograph: responsive WebP sources from the media conversions, the
 * treatment class (warm monochrome portrait or evergreen duotone archive) and a
 * place/year caption in the label style. `tone` is the caption ink: "ivory" on
 * evergreen surfaces (brass), "evergreen" on ivory surfaces (bronze).
 */
class Photo extends Component
{
    public string $src;

    public ?string $srcset;

    public string $alt;

    public string $treatmentClass;

    public ?string $caption;

    public ?string $placeYear;

    public function __construct(
        public Media $media,
        public string $tone = 'evergreen',
        public string $sizes = '100vw',
        public string $loading = 'lazy',
        public bool $showCaption = false,
        public ?string $aspect = null,
    ) {
        $this->src = $this->largestUrl();
        $this->srcset = $this->srcset();
        $this->alt = (string) ($media->getCustomProperty(MediaProperties::ALT_TEXT) ?? '');
        $this->treatmentClass = $this->treatmentClass();
        $this->caption = $this->text(MediaProperties::CAPTION);
        $this->placeYear = $this->placeYear();
    }

    public function onDark(): bool
    {
        return $this->tone === 'ivory';
    }

    public function hasCaption(): bool
    {
        return $this->placeYear !== null || ($this->showCaption && $this->caption !== null);
    }

    public function render(): View
    {
        return view('components.photo');
    }

    /**
     * Largest generated conversion, falling back to the original upload.
     */
    private function largestUrl(): string
    {
        foreach (array_reverse(MediaConversions::names()) as $name) {
            if ($this->media->hasGeneratedConversion($name)) {
                return $this->media->getUrl($name);
            }
        }

        return $this->media->getUrl();
    }

    private function srcset(): ?string
    {
        $sources = [];

        foreach (MediaConversions::widths() as $name => $width) {
            if ($this->media->hasGeneratedConversion($name)) {
                $sources[] = $this->media->getUrl($name).' '.$width.'w';
            }
        }

        return $sources === [] ? null : implode(', ', $sources);
    }

    private function treatmentClass(): string
    {
        $treatment = MediaTreatment::tryFrom((string) $this->media->getCustomProperty(MediaProperties::TREATMENT));

        return match ($treatment) {
            MediaTreatment::Archive => 'photo-archive',
            default => 'photo-portrait',
        };
    }

    private function placeYear(): ?string
    {
        $parts = array_filter([$this->text(MediaProperties::PLACE), $this->text(MediaProperties::YEAR)]);

        return $parts === [] ? null : implode(', ', $parts);
    }

    private function text(string $property): ?string
    {
        $value = $this->media->getCustomProperty($property);

        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return trim((string) $value);
    }
}
