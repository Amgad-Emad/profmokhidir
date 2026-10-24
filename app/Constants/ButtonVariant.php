<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The five button variants of the design system (<x-button variant="…">). Each one
 * takes its background, text, border and icon colours for every ButtonState from the
 * `.btn` token table in resources/css/tokens.css (--btn-{variant}-{part}[-{state}]);
 * resources/css/buttons.css applies them. Inside an `.on-dark` region the same
 * variant follows the band (primary becomes the ivory pill), so there is no
 * separate "light" variant. tests/Unit/Design/ButtonContrastTest checks every
 * variant, state and theme from the tokens.
 */
enum ButtonVariant: string
{
    /** The one main action of a form or screen: a solid pill (evergreen on ivory, ivory on dark). */
    case Primary = 'primary';

    /** Other page actions: the outline pill in the current ink. */
    case Secondary = 'secondary';

    /** Back, Cancel and row actions: underlined text with a 44px target. */
    case Quiet = 'quiet';

    /** A tool with an icon and no visible label (it needs an accessible name). */
    case Icon = 'icon';

    /** An action that removes or blocks something: the outline pill in the danger tone. */
    case Danger = 'danger';

    /** The colour parts every variant declares for every state. */
    public const array PARTS = ['bg', 'ink', 'border', 'icon'];

    /** The minimum contrast of a label or icon against its button (WCAG 1.4.3). */
    public const float LABEL_CONTRAST = 4.5;

    /** The minimum contrast of a pill's border against the surface (WCAG 1.4.11). */
    public const float BORDER_CONTRAST = 3.0;

    public function cssClass(): string
    {
        return 'btn--'.$this->value;
    }

    /** Whether the variant draws a visible border that marks the control (outline pills). */
    public function hasBoundary(): bool
    {
        return in_array($this, [self::Secondary, self::Danger], true);
    }

    /**
     * The token name of one colour part in one state, e.g. --btn-primary-bg-hover.
     */
    public function token(string $part, ButtonState $state = ButtonState::Default): string
    {
        return '--btn-'.$this->value.'-'.$part.$state->tokenSuffix();
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $variant): string => $variant->value, self::cases());
    }
}
