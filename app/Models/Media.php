<?php

declare(strict_types=1);

namespace App\Models;

use App\Constants\Locales;
use App\Constants\MediaProperties;
use Illuminate\Support\Arr;
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
    /**
     * Keep every translation nested (translations.ar.caption as
     * ['translations' => ['ar' => ['caption' => ...]]]) whatever wrote it: a
     * literal "translations.ar.caption" key would shadow the nested value on
     * every read, so edits looked lost.
     */
    protected static function booted(): void
    {
        static::saving(function (self $media): void {
            $media->custom_properties = self::foldTranslationKeys((array) $media->custom_properties);
        });
    }

    /**
     * Move literal "translations.<locale>.<property>" keys into the nested
     * translations. A filled nested value wins (it is the one editors saved);
     * the flat one only fills a gap.
     *
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    public static function foldTranslationKeys(array $properties): array
    {
        foreach (array_keys($properties) as $key) {
            if (! is_string($key) || ! str_contains($key, '.') || ! MediaProperties::isTranslationPath($key)) {
                continue;
            }

            $value = $properties[$key];
            unset($properties[$key]);

            if (blank(Arr::get($properties, $key))) {
                $value === null || $value === '' ? Arr::forget($properties, $key) : Arr::set($properties, $key, $value);
            }
        }

        return $properties;
    }

    /**
     * Fold any stale flat key first, so the change lands on the value that is read.
     */
    public function setCustomProperty(string $name, $value): self
    {
        $this->custom_properties = self::foldTranslationKeys((array) $this->custom_properties);

        return parent::setCustomProperty($name, $value);
    }

    public function forgetCustomProperty(string $name): self
    {
        $properties = (array) $this->custom_properties;

        // Clearing a translation clears the flat copy too, so the old text cannot come back.
        unset($properties[$name]);
        $this->custom_properties = self::foldTranslationKeys($properties);

        return parent::forgetCustomProperty($name);
    }

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
