<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\ButtonSize;
use App\Constants\ButtonVariant;
use InvalidArgumentException;

/**
 * Resolves <x-button> props to the design system's classes: `.btn`, the variant
 * and the size. Colours never come from the caller: utility classes for text,
 * background or border colour on a button are refused here, because a Tailwind
 * utility outranks the component's hover rule and was what left a label the
 * colour of its own fill.
 */
class ButtonService
{
    /** Utility prefixes that would override a button's token colours. */
    private const string COLOUR_UTILITY = '/(^|\s)([a-z-]+:)*(text|bg|border|decoration|fill|stroke)-(ink|surface|fill|accent|danger|pending|success|neutral|rule|focus|selection|white|black|current|transparent)[\w\/-]*/';

    public function variant(ButtonVariant|string|null $variant): ButtonVariant
    {
        if ($variant instanceof ButtonVariant) {
            return $variant;
        }

        return ButtonVariant::tryFrom((string) $variant)
            ?? throw new InvalidArgumentException("Unknown button variant [{$variant}]; use one of: ".implode(', ', ButtonVariant::values()).'.');
    }

    public function size(ButtonSize|string|null $size): ButtonSize
    {
        if ($size instanceof ButtonSize) {
            return $size;
        }

        return ButtonSize::tryFrom((string) ($size ?? ButtonSize::Medium->value))
            ?? throw new InvalidArgumentException("Unknown button size [{$size}].");
    }

    /**
     * The class list for a button. `$onDark` makes the button itself an `.on-dark`
     * region, for a button on a dark surface whose ancestors are not marked.
     */
    public function classes(ButtonVariant|string|null $variant, ButtonSize|string|null $size = null, bool $onDark = false): string
    {
        return implode(' ', array_filter([
            'btn',
            $this->variant($variant)->cssClass(),
            $this->size($size)->cssClass(),
            $onDark ? 'on-dark' : null,
        ]));
    }

    /**
     * Refuse colour utilities passed in `class` (layout utilities such as w-full or mt-8 are fine).
     */
    public function assertNoColourUtilities(?string $classes): void
    {
        if ($classes !== null && preg_match(self::COLOUR_UTILITY, $classes, $match)) {
            throw new InvalidArgumentException('Buttons take their colours from the variant tokens; remove ['.trim($match[0]).'].');
        }
    }
}
