<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Brand colour palette. These nine values are the only colours in the UI,
 * mirrored one-to-one by the Tailwind theme in resources/css/app.css.
 */
final class Brand
{
    public const string EVERGREEN = '#13221C';

    public const string IVORY = '#F3F1EA';

    public const string CYPRESS = '#2F4A3E';

    public const string BRASS = '#A8864F';

    public const string CHAMPAGNE = '#DCCDA8';

    public const string LINEN = '#E7E5DC';

    public const string STONE = '#D3D1C5';

    public const string ASH = '#575A55';

    public const string BRONZE = '#6F5A34';

    /** Share of surfaces each colour should roughly occupy, as a percentage. */
    public const int EVERGREEN_SHARE = 40;

    public const int IVORY_SHARE = 45;

    public const int CYPRESS_SHARE = 10;

    public const int BRASS_SHARE = 5;

    /** Below this font size brass lacks contrast on ivory, so bronze is used instead. */
    public const int BRASS_MIN_TEXT_PX = 24;

    /**
     * Token name => hex, in the order they appear in the brand guide.
     *
     * @return array<string, string>
     */
    public static function colours(): array
    {
        return [
            'evergreen' => self::EVERGREEN,
            'ivory' => self::IVORY,
            'cypress' => self::CYPRESS,
            'brass' => self::BRASS,
            'champagne' => self::CHAMPAGNE,
            'linen' => self::LINEN,
            'stone' => self::STONE,
            'ash' => self::ASH,
            'bronze' => self::BRONZE,
        ];
    }

    /**
     * Intended use of each colour, for the style guide and code review.
     *
     * @return array<string, array{role: string, usage: string}>
     */
    public static function roles(): array
    {
        return [
            'evergreen' => ['role' => 'Primary', 'usage' => 'Dark surfaces, about '.self::EVERGREEN_SHARE.'% of the page. Body text on ivory, and the one primary pill of a form on ivory.'],
            'ivory' => ['role' => 'Primary', 'usage' => 'Light surfaces, about '.self::IVORY_SHARE.'% of the page. Text and filled buttons on evergreen.'],
            'cypress' => ['role' => 'Support', 'usage' => 'Secondary dark surfaces and hover states, about '.self::CYPRESS_SHARE.'%.'],
            'brass' => ['role' => 'Accent', 'usage' => 'About '.self::BRASS_SHARE.'%: the mark ring, thin rules and small labels on dark only. Never large text or fills.'],
            'champagne' => ['role' => 'Text', 'usage' => 'Body and UI text on evergreen.'],
            'linen' => ['role' => 'Neutral', 'usage' => 'Card surfaces on ivory, selection highlight.'],
            'stone' => ['role' => 'Neutral', 'usage' => 'Hairline borders and dividers on light surfaces.'],
            'ash' => ['role' => 'Neutral', 'usage' => 'Secondary text on light surfaces.'],
            'bronze' => ['role' => 'Accent', 'usage' => 'Replaces brass for small text and focus rings on ivory (brass lacks contrast under '.self::BRASS_MIN_TEXT_PX.'px).'],
        ];
    }

    /**
     * Non-brand values that remain available in Tailwind (white, black, transparent, current).
     *
     * @return list<string>
     */
    public static function permittedExtras(): array
    {
        return ['#FFFFFF', '#000000'];
    }

    public static function isBrandColour(string $hex): bool
    {
        return in_array(strtoupper($hex), self::colours(), true);
    }
}
