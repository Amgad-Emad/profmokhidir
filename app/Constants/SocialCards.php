<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Static Open Graph share images. Each card is rendered once by
 * `php artisan brand:social-cards` into public/ and served as a plain file;
 * nothing is drawn at request time. Each card also has an Arabic edition
 * (the quote in Arabic, shaped and set in Amiri), shared from Arabic pages:
 * forLocale() picks the edition for the current language.
 */
final class SocialCards
{
    public const int WIDTH = 1200;

    public const int HEIGHT = 630;

    /** The line every card carries, in the quote style. */
    public const string QUOTE = 'Knowledge is the light we pass on.';

    /** Shown in label style along the foot of the card. */
    public const string DOMAIN = 'profmokhidir.com';

    /** The card every page shares unless a section has its own. */
    public const string SITE = 'images/social/site.png';

    /** Card key => path under public/. */
    public const string TRIBUTES = 'images/social/tributes.png';

    /** Arabic editions of the cards above. */
    public const string SITE_AR = 'images/social/site-ar.png';

    public const string TRIBUTES_AR = 'images/social/tributes-ar.png';

    /** Ring diameter of the mark on the card, in pixels. */
    public const int MARK_SIZE_PX = 96;

    /** Key of the shared card in all(). */
    public const string KEY_SITE = 'site';

    /** Fonts used to draw the cards, relative to resources/fonts. */
    public const string FONT_DISPLAY_ITALIC = 'CormorantGaramond-LightItalic.ttf';

    public const string FONT_DISPLAY = 'CormorantGaramond-Light.ttf';

    public const string FONT_LABEL = 'HankenGrotesk-Medium.ttf';

    /** Amiri (OFL) replaces the Latin faces for Arabic text: regular for the quote and names, bold for labels. */
    public const string FONT_ARABIC = 'Amiri-Regular.ttf';

    public const string FONT_ARABIC_BOLD = 'Amiri-Bold.ttf';

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [self::KEY_SITE => self::SITE, Sections::TRIBUTES => self::TRIBUTES];
    }

    /**
     * Every card in one language, keyed like all().
     *
     * @return array<string, string>
     */
    public static function allFor(string $locale): array
    {
        return array_map(fn (string $path): string => self::forLocale($path, $locale), self::all());
    }

    /**
     * The edition of a card for a language (the current one by default); English
     * and any card without a translated edition keep the given path.
     */
    public static function forLocale(string $path, ?string $locale = null): string
    {
        $editions = [Locales::AR => [self::SITE => self::SITE_AR, self::TRIBUTES => self::TRIBUTES_AR]];

        return $editions[$locale ?? app()->getLocale()][$path] ?? $path;
    }

    public static function for(string $section): ?string
    {
        return self::all()[$section] ?? null;
    }

    /**
     * The card for a section, or the shared site card.
     */
    public static function forOrDefault(?string $section): string
    {
        return $section === null ? self::SITE : (self::for($section) ?? self::SITE);
    }
}
