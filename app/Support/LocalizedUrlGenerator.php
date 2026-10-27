<?php

declare(strict_types=1);

namespace App\Support;

use App\Constants\Locales;
use BackedEnum;
use Illuminate\Routing\UrlGenerator;

/**
 * Keeps every route() call in the language of the page. While Arabic is the
 * current locale, route('publications') resolves to the Arabic twin
 * (ar.publications, /ar/publications) when one exists; routes without a twin
 * (admin, chat, sign-in) are left alone. routeIn() asks for a given language
 * outright (the switcher, hreflang links, sitemaps).
 */
class LocalizedUrlGenerator extends UrlGenerator
{
    /** Set while routeIn() resolves, so the name it passes is taken as it is. */
    private bool $exact = false;

    public function route($name, $parameters = [], $absolute = true)
    {
        if ($name instanceof BackedEnum) {
            $name = (string) $name->value;
        }

        if (! $this->exact && is_string($name)) {
            $locale = app()->getLocale();

            if ($locale !== Locales::DEFAULT && Locales::isValid($locale)) {
                $localized = Locales::routeName($name, $locale);

                if ($localized !== $name && $this->routes->getByName($localized) !== null) {
                    $name = $localized;
                }
            }
        }

        return parent::route($name, $parameters, $absolute);
    }

    /**
     * A route's address in the given language (its own route when the
     * language has no twin of it).
     *
     * @param  mixed  $parameters
     */
    public function routeIn(string $locale, string $name, $parameters = [], bool $absolute = true): string
    {
        $localized = Locales::routeName($name, $locale);

        if ($this->routes->getByName($localized) === null) {
            $localized = Locales::baseRouteName($name);
        }

        $this->exact = true;

        try {
            return parent::route($localized, $parameters, $absolute);
        } finally {
            $this->exact = false;
        }
    }

    /**
     * Whether the named route has a twin in the language.
     */
    public function hasRouteIn(string $locale, string $name): bool
    {
        return $this->routes->getByName(Locales::routeName($name, $locale)) !== null;
    }
}
