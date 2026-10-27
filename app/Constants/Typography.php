<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Brand typefaces and type scale. The faces are self-hosted WOFF2 files (no
 * request leaves the site before the visitor acts). Sizes are the desktop values
 * from the brand guide; the CSS classes scale display and heading sizes down
 * fluidly on small screens.
 */
final class Typography
{
    public const string DISPLAY_FAMILY = 'Cormorant Garamond';

    public const string SANS_FAMILY = 'Hanken Grotesk';

    public const string MONO_FAMILY = 'JetBrains Mono';

    /*
     | Arabic (a documented exception to the brand type rules): Cormorant Garamond and Hanken
     | Grotesk have no Arabic glyphs, so Arabic pages (<html lang="ar">) add Amiri 400/700 for
     | display, headings and quotes and IBM Plex Sans Arabic 400/500 for body and UI. They are
     | loaded from Google Fonts with display=swap on Arabic pages only (<x-fonts>), and the
     | :lang(ar) stacks in resources/css/locale.css name the Latin brand face first: its
     | self-hosted file covers Latin only (unicode-range), so English words, Western digits and
     | Latin punctuation inside Arabic text keep the brand faces and Arabic letters fall through to
     | the Arabic face. (The Google CSS also ships Latin subsets of both Arabic families, so putting
     | them first would set English words in Amiri and Plex.) Arabic has no letter-spacing, no
     | capitals, no italics, no old-style figures, small caps or discretionary ligatures and no drop
     | caps; sizes and leading are a step larger (see ARABIC_* below). JetBrains Mono is unchanged.
     */
    public const string ARABIC_DISPLAY_FAMILY = 'Amiri';

    public const string ARABIC_SANS_FAMILY = 'IBM Plex Sans Arabic';

    /** Google Fonts origins, preconnected on Arabic pages only. */
    public const string GOOGLE_FONTS_ORIGIN = 'https://fonts.googleapis.com';

    public const string GOOGLE_FONTS_STATIC_ORIGIN = 'https://fonts.gstatic.com';

    /** The one third-party stylesheet, printed by <x-fonts> on Arabic pages only. */
    public const string ARABIC_STYLESHEET = 'https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=IBM+Plex+Sans+Arabic:wght@400;500&display=swap';

    /** The locale whose pages load the Arabic faces. */
    public const string ARABIC_LOCALE = 'ar';

    /** Arabic body: 19px desktop, 18px phones, at line-height 1.8; labels 14px, no tracking, no capitals. */
    public const int ARABIC_BODY_PX = 19;

    public const int ARABIC_BODY_MOBILE_PX = 18;

    public const float ARABIC_BODY_LINE_HEIGHT = 1.8;

    public const int ARABIC_LABEL_PX = 14;

    /** Self-hosted WOFF2 files under public/fonts (latin subset, from Google Fonts). */
    public const string FONTS_DIRECTORY = 'fonts';

    public const string FILE_DISPLAY = 'cormorant-garamond-light.woff2';

    public const string FILE_DISPLAY_ITALIC = 'cormorant-garamond-light-italic.woff2';

    public const string FILE_SANS = 'hanken-grotesk.woff2';

    public const string FILE_MONO = 'jetbrains-mono.woff2';

    /** The one file worth preloading: the display face draws the first heading. */
    public const string PRELOAD_FILE = self::FILE_DISPLAY;

    /**
     * Font files served from public/fonts, as the @font-face rules in
     * resources/css/fonts.css declare them. Weights are a range for the
     * variable files and a single value otherwise.
     *
     * @return list<array{file: string, family: string, style: string, weight: string}>
     */
    public static function files(): array
    {
        return [
            ['file' => self::FILE_DISPLAY, 'family' => self::DISPLAY_FAMILY, 'style' => 'normal', 'weight' => '300'],
            ['file' => self::FILE_DISPLAY_ITALIC, 'family' => self::DISPLAY_FAMILY, 'style' => 'italic', 'weight' => '300'],
            ['file' => self::FILE_SANS, 'family' => self::SANS_FAMILY, 'style' => 'normal', 'weight' => '400 500'],
            ['file' => self::FILE_MONO, 'family' => self::MONO_FAMILY, 'style' => 'normal', 'weight' => '400'],
        ];
    }

    /**
     * Public path of a font file, relative to the document root.
     */
    public static function path(string $file): string
    {
        return self::FONTS_DIRECTORY.'/'.$file;
    }

    /**
     * Font token => family and loaded weights.
     *
     * @return array<string, array{family: string, weights: list<int>, italic: bool, use: string}>
     */
    public static function fonts(): array
    {
        return [
            'display' => ['family' => self::DISPLAY_FAMILY, 'weights' => [300], 'italic' => true, 'use' => 'Display, headings and quotes.'],
            'sans' => ['family' => self::SANS_FAMILY, 'weights' => [400, 500], 'italic' => false, 'use' => 'Body 400; emphasis, labels and UI 500. 300 is not used for running text.'],
            'mono' => ['family' => self::MONO_FAMILY, 'weights' => [400], 'italic' => false, 'use' => 'Technical specs only.'],
        ];
    }

    /**
     * Whether a locale's pages load the Arabic faces.
     */
    public static function loadsArabicFaces(string $locale): bool
    {
        return $locale === self::ARABIC_LOCALE;
    }

    /**
     * The Arabic faces (the exception described above): token => family, weights and use.
     *
     * @return array<string, array{family: string, weights: list<int>, italic: bool, use: string}>
     */
    public static function arabicFonts(): array
    {
        return [
            'display' => ['family' => self::ARABIC_DISPLAY_FAMILY, 'weights' => [400, 700], 'italic' => false, 'use' => 'Arabic display, headings and quotes (where Cormorant Garamond is used); 700 only to emphasise.'],
            'sans' => ['family' => self::ARABIC_SANS_FAMILY, 'weights' => [400, 500], 'italic' => false, 'use' => 'Arabic body 400; emphasis, labels and UI 500 (where Hanken Grotesk is used).'],
        ];
    }

    /**
     * Type scale: CSS class => specification.
     *
     * @return array<string, array{class: string, size: int, mobile: int, font: string, weight: int, italic: bool, lineHeight: float, tracking: ?string, uppercase: bool, sample: string}>
     */
    public static function scale(): array
    {
        return [
            'display' => ['class' => 't-display', 'size' => 96, 'mobile' => 52, 'font' => self::DISPLAY_FAMILY, 'weight' => 300, 'italic' => false, 'lineHeight' => 1.0, 'tracking' => null, 'uppercase' => false, 'sample' => 'Knowledge is the light we pass on.'],
            'heading' => ['class' => 't-heading', 'size' => 48, 'mobile' => 34, 'font' => self::DISPLAY_FAMILY, 'weight' => 300, 'italic' => false, 'lineHeight' => 1.1, 'tracking' => null, 'uppercase' => false, 'sample' => 'A life devoted to knowledge and to those who sought it.'],
            'quote' => ['class' => 't-quote', 'size' => 32, 'mobile' => 32, 'font' => self::DISPLAY_FAMILY, 'weight' => 300, 'italic' => true, 'lineHeight' => 1.3, 'tracking' => null, 'uppercase' => false, 'sample' => 'His lessons continue in every student he taught.'],
            'body' => ['class' => 't-body', 'size' => 18, 'mobile' => 17, 'font' => self::SANS_FAMILY, 'weight' => 400, 'italic' => false, 'lineHeight' => 1.6, 'tracking' => null, 'uppercase' => false, 'sample' => 'Teacher, scholar and mentor. Over five decades Professor Khidir taught, supervised and encouraged generations of students, and his writing continues to shape the field.'],
            'label' => ['class' => 't-label', 'size' => 12, 'mobile' => 12, 'font' => self::SANS_FAMILY, 'weight' => 500, 'italic' => false, 'lineHeight' => 1.4, 'tracking' => '0.24em', 'uppercase' => true, 'sample' => 'Khartoum, 1974'],
        ];
    }
}
