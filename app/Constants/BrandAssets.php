<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The logo artwork (open book, sprout and sunrays) as static files under public/.
 * Every logo comes in two colourways: "on-dark" (ivory book, sage leaves, brass rays)
 * for evergreen bands and "on-light" (green book, brass rays) for the ivory page.
 * The SVGs carry the name in outlined capitals, so they need no web font; their
 * colours are fixed, so the logo keeps its brand colours under every theme.
 */
final class BrandAssets
{
    /** Colourway for dark surfaces. */
    public const string ON_DARK = 'on-dark';

    /** Colourway for light surfaces. */
    public const string ON_LIGHT = 'on-light';

    /** The mark alone (book, sprout and rays): `%s` is the colourway. */
    public const string MARK = 'images/brand/mark-%s.svg';

    /** The square app icon, used below BrandMark::DISC_BELOW_PX where the full mark loses its rays. */
    public const string ICON = 'images/brand/icon-evergreen.svg';

    /** Mark left of "Professor / Mohamed Osman Khidir" on one line (header, chat bar). */
    public const string LOGO_HORIZONTAL = 'images/brand/logo-horizontal-%s.svg';

    /** Mark left of the name set on two lines (phones, admin sidebar). */
    public const string LOGO_COMPACT = 'images/brand/logo-compact-%s.svg';

    /** Mark over the name, with the tagline beneath. */
    public const string LOGO_STACKED = 'images/brand/logo-stacked-%s.svg';

    /** Mark over the name, where the tagline is set separately as live text. */
    public const string LOGO_STACKED_NO_TAGLINE = 'images/brand/logo-stacked-no-tagline-%s.svg';

    /** Width / height of each artwork's viewBox, for width and height attributes (no layout shift). */
    public const float MARK_RATIO = 530 / 363;

    public const float ICON_RATIO = 1.0;

    public const float LOGO_HORIZONTAL_RATIO = 625.801 / 108;

    public const float LOGO_COMPACT_RATIO = 452.372 / 110;

    public const float LOGO_STACKED_RATIO = 864 / 565;

    public const float LOGO_STACKED_NO_TAGLINE_RATIO = 864 / 513;

    /** Raster mark on transparent ground, drawn onto the share cards (BrandCanvas::mark). */
    public const string MARK_PNG_ON_DARK = 'images/brand/png/mark-on-dark.png';

    /** Logo at the top of transactional emails: 600px wide, shown at EMAIL_LOGO_WIDTH_PX. */
    public const string EMAIL_LOGO = 'images/brand/email/email-logo.png';

    public const int EMAIL_LOGO_WIDTH_PX = 300;

    /** Favicons, touch icons and the web app manifest at the root of public/. */
    public const string FAVICON_ICO = 'favicon.ico';

    public const string FAVICON_SVG = 'favicon.svg';

    public const string FAVICON_32 = 'favicon-32x32.png';

    public const string FAVICON_16 = 'favicon-16x16.png';

    public const string APPLE_TOUCH_ICON = 'apple-touch-icon.png';

    public const string SAFARI_PINNED_TAB = 'safari-pinned-tab.svg';

    public const string MANIFEST = 'site.webmanifest';

    /** Neutral stand-ins for missing media: `%s` is "light" or "dark". */
    public const string PLACEHOLDER_AVATAR = 'images/placeholders/avatar-%s.svg';

    public const string PLACEHOLDER_IMAGE_16X9 = 'images/placeholders/image-16x9-%s.svg';

    public const string PLACEHOLDER_IMAGE_4X3 = 'images/placeholders/image-4x3-%s.svg';

    public const string PLACEHOLDER_IMAGE_1X1 = 'images/placeholders/image-1x1-%s.svg';

    public const string PLACEHOLDER_PORTRAIT = 'images/placeholders/portrait-4x5-%s.svg';

    public const string PLACEHOLDER_VIDEO = 'images/placeholders/video-16x9-%s.svg';

    public const string PLACEHOLDER_PUBLICATION_COVER = 'images/placeholders/publication-cover-%s.svg';

    /**
     * @return list<string>
     */
    public static function colourways(): array
    {
        return [self::ON_DARK, self::ON_LIGHT];
    }

    /** Path under public/ of an artwork in a colourway (dark when the colourway is unknown). */
    public static function path(string $artwork, string $colourway = self::ON_DARK): string
    {
        return sprintf($artwork, in_array($colourway, self::colourways(), true) ? $colourway : self::ON_DARK);
    }

    /** Rendered width in whole pixels for a height and an artwork ratio. */
    public static function widthFor(int $height, float $ratio): int
    {
        return (int) round($height * $ratio);
    }
}
