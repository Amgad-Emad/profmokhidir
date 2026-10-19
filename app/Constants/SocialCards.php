<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Static Open Graph share images. Each card is rendered once by
 * `php artisan brand:social-cards` into public/ and served as a plain file;
 * nothing is drawn at request time.
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

    /** Ring diameter of the mark on the card, in pixels. */
    public const int MARK_SIZE_PX = 96;

    /** Key of the shared card in all(). */
    public const string KEY_SITE = 'site';

    /** Fonts used to draw the cards, relative to resources/fonts. */
    public const string FONT_DISPLAY_ITALIC = 'CormorantGaramond-LightItalic.ttf';

    public const string FONT_DISPLAY = 'CormorantGaramond-Light.ttf';

    public const string FONT_LABEL = 'HankenGrotesk-Medium.ttf';

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [self::KEY_SITE => self::SITE, Sections::TRIBUTES => self::TRIBUTES];
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
