<?php

declare(strict_types=1);

namespace App\Models;

use App\Constants\Locales;
use App\Constants\MediaProperties;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

/**
 * Media items with English and Arabic descriptions. The English alt text,
 * caption, place and note are the plain custom properties; their Arabic
 * versions live under translations.ar (MediaProperties::TRANSLATIONS).
 * Reading one of those properties gives the current language's value, else
 * the English one; everything else reads as stored.
 */
class Media extends BaseMedia
{
    public function getCustomProperty(string $propertyName, $default = null): mixed
    {
        $locale = app()->getLocale();

        if ($locale !== Locales::DEFAULT && in_array($propertyName, MediaProperties::translatable(), true)) {
            $translated = parent::getCustomProperty(MediaProperties::translationPath($locale, $propertyName));

            if (filled($translated)) {
                return $translated;
            }
        }

        return parent::getCustomProperty($propertyName, $default);
    }

    /**
     * A described property in one language, without falling back.
     */
    public function getTranslatedProperty(string $propertyName, string $locale): mixed
    {
        return $locale === Locales::DEFAULT
            ? parent::getCustomProperty($propertyName)
            : parent::getCustomProperty(MediaProperties::translationPath($locale, $propertyName));
    }

    /**
     * Whether the image's described properties that are filled in English are
     * all filled in the language too.
     */
    public function needsTranslation(string $locale = Locales::AR): bool
    {
        foreach (MediaProperties::translatable() as $property) {
            if (filled(parent::getCustomProperty($property)) && blank($this->getTranslatedProperty($property, $locale))) {
                return true;
            }
        }

        return false;
    }
}
