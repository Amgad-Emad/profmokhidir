<?php

declare(strict_types=1);

namespace App\Constants;

use Illuminate\Support\HtmlString;

/**
 * The site's languages. English is the default and keeps the plain addresses
 * (/publications); every other language lives under its own prefix
 * (/ar/publications) with route names prefixed the same way (ar.publications).
 * Admin, chat and the sign-in screens have no prefix: they follow the
 * signed-in user's saved language, else the cookie, else English.
 */
final class Locales
{
    public const string EN = 'en';

    public const string AR = 'ar';

    public const string DEFAULT = self::EN;

    public const string LTR = 'ltr';

    public const string RTL = 'rtl';

    /** Cookie holding the visitor's choice (plain, so the head script and the switcher can read it). */
    public const string COOKIE = 'locale';

    /** A year, in minutes. */
    public const int COOKIE_MINUTES = 525600;

    /** Cookie set when the visitor dismisses the "read the site in Arabic" suggestion. */
    public const string SUGGESTION_COOKIE = 'locale_hint';

    /** Column on users (NULL until the person chooses). */
    public const string COLUMN = 'locale';

    /** The switcher's route: /language/{locale}?to=/ar/publications. */
    public const string SWITCH_ROUTE = 'locale.switch';

    public const string SWITCH_PATH = '/language';

    /** Query field naming where the switcher goes after storing the choice. */
    public const string TARGET_PARAM = 'to';

    /** Route action key carrying the fixed language of a public route. */
    public const string ACTION_KEY = 'locale';

    /** Site setting: Arabic pages print running-text numbers in Arabic-Indic digits. */
    public const string DIGITS_LATIN = 'latin';

    public const string DIGITS_ARABIC = 'arabic';

    /**
     * @return array<string, array{native: string, english: string, dir: string, html: string, og: string, prefix: string, hreflang: string}>
     */
    public static function definitions(): array
    {
        return [
            self::EN => ['native' => 'English', 'english' => 'English', 'dir' => self::LTR, 'html' => 'en', 'og' => 'en_GB', 'prefix' => '', 'hreflang' => 'en'],
            self::AR => ['native' => 'العربية', 'english' => 'Arabic', 'dir' => self::RTL, 'html' => 'ar', 'og' => 'ar_AR', 'prefix' => 'ar', 'hreflang' => 'ar'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * Languages with an address prefix (every one but the default).
     *
     * @return list<string>
     */
    public static function prefixed(): array
    {
        return array_values(array_diff(self::all(), [self::DEFAULT]));
    }

    public static function isValid(mixed $locale): bool
    {
        return is_string($locale) && array_key_exists($locale, self::definitions());
    }

    public static function dir(string $locale): string
    {
        return self::definitions()[$locale]['dir'] ?? self::LTR;
    }

    public static function isRtl(string $locale): bool
    {
        return self::dir($locale) === self::RTL;
    }

    public static function native(string $locale): string
    {
        return self::definitions()[$locale]['native'];
    }

    public static function html(string $locale): string
    {
        return self::definitions()[$locale]['html'];
    }

    public static function og(string $locale): string
    {
        return self::definitions()[$locale]['og'];
    }

    public static function prefix(string $locale): string
    {
        return self::definitions()[$locale]['prefix'];
    }

    /**
     * The other language (the switcher offers it). With two languages this is
     * the one that is not current.
     */
    public static function other(string $locale): string
    {
        return $locale === self::DEFAULT ? self::AR : self::DEFAULT;
    }

    /**
     * A route name in a language: "publications" becomes "ar.publications".
     */
    public static function routeName(string $name, string $locale): string
    {
        $base = self::baseRouteName($name);

        return $locale === self::DEFAULT ? $base : $locale.'.'.$base;
    }

    /**
     * A route name without its language prefix ("ar.publications" => "publications").
     */
    public static function baseRouteName(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        foreach (self::prefixed() as $locale) {
            if (str_starts_with($name, $locale.'.')) {
                return substr($name, strlen($locale) + 1);
            }
        }

        return $name;
    }

    /**
     * The language a path belongs to by its prefix ("ar/publications" => ar).
     */
    public static function fromPath(string $path): string
    {
        $first = explode('/', trim($path, '/'))[0];

        return in_array($first, self::prefixed(), true) ? $first : self::DEFAULT;
    }

    /**
     * `lang="en" dir="ltr"` for content shown in another language than the
     * page's (an English fallback on an Arabic page), else nothing. Takes a
     * translatable model and optionally the column shown.
     */
    public static function attributesFor(mixed $model, ?string $column = null): HtmlString
    {
        if (! is_object($model) || ! method_exists($model, 'shownLocale')) {
            return new HtmlString('');
        }

        $shown = $model->shownLocale($column);

        return new HtmlString($shown === app()->getLocale() ? '' : sprintf('lang="%s" dir="%s"', self::html($shown), self::dir($shown)));
    }

    /**
     * Whether a value is a map of the site's languages to values (['en' => …, 'ar' => …]).
     */
    public static function isLocaleMap(mixed $value): bool
    {
        return is_array($value) && $value !== [] && ! array_is_list($value)
            && array_diff(array_keys($value), self::all()) === [];
    }

    /**
     * @return list<string>
     */
    public static function digitStyles(): array
    {
        return [self::DIGITS_LATIN, self::DIGITS_ARABIC];
    }
}
