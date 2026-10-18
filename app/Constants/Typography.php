<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Brand typefaces and type scale. Sizes are the desktop values from the brand
 * guide; the CSS classes scale display and heading sizes down fluidly on small screens.
 */
final class Typography
{
    public const string DISPLAY_FAMILY = 'Cormorant Garamond';

    public const string SANS_FAMILY = 'Hanken Grotesk';

    public const string MONO_FAMILY = 'JetBrains Mono';

    public const string GOOGLE_FONTS_URL = 'https://fonts.googleapis.com/css2'
        .'?family=Cormorant+Garamond:ital,wght@0,300;1,300'
        .'&family=Hanken+Grotesk:wght@300;400;500'
        .'&family=JetBrains+Mono:wght@400'
        .'&display=swap';

    /**
     * Origins to preconnect to before the font stylesheet is requested.
     *
     * @return list<array{href: string, crossorigin: bool}>
     */
    public static function preconnects(): array
    {
        return [
            ['href' => 'https://fonts.googleapis.com', 'crossorigin' => false],
            ['href' => 'https://fonts.gstatic.com', 'crossorigin' => true],
        ];
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
            'sans' => ['family' => self::SANS_FAMILY, 'weights' => [300, 400, 500], 'italic' => false, 'use' => 'Body and UI. 300 for reading, 500 for emphasis.'],
            'mono' => ['family' => self::MONO_FAMILY, 'weights' => [400], 'italic' => false, 'use' => 'Technical specs only.'],
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
            'body' => ['class' => 't-body', 'size' => 18, 'mobile' => 18, 'font' => self::SANS_FAMILY, 'weight' => 300, 'italic' => false, 'lineHeight' => 1.7, 'tracking' => null, 'uppercase' => false, 'sample' => 'Teacher, scholar and mentor. Over five decades Professor Khidir taught, supervised and encouraged generations of students, and his writing continues to shape the field.'],
            'label' => ['class' => 't-label', 'size' => 12, 'mobile' => 12, 'font' => self::SANS_FAMILY, 'weight' => 500, 'italic' => false, 'lineHeight' => 1.4, 'tracking' => '0.3em', 'uppercase' => true, 'sample' => 'Khartoum, 1974'],
        ];
    }
}
