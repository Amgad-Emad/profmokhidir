<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The states every form control can be in, defined once in resources/css/forms.css
 * with the semantic tokens and shown side by side on /styleguide in both themes.
 * Each state names the hook the stylesheet keys on.
 */
final class FormStates
{
    public const string DEFAULT = 'default';

    public const string HOVER = 'hover';

    public const string FOCUS = 'focus';

    public const string FOCUS_KEYBOARD = 'focus-keyboard';

    public const string FILLED = 'filled';

    public const string INVALID = 'invalid';

    public const string VALID = 'valid';

    public const string DISABLED = 'disabled';

    public const string READONLY = 'readonly';

    public const string LOADING = 'loading';

    /**
     * State => label, what it looks like and the hook that triggers it.
     *
     * @return array<string, array{label: string, look: string, hook: string}>
     */
    public static function all(): array
    {
        return [
            self::DEFAULT => ['label' => 'Default', 'look' => 'A 1px quiet rule on the field surface.', 'hook' => '.form-control'],
            self::HOVER => ['label' => 'Hover', 'look' => 'The rule darkens to the strong rule.', 'hook' => ':hover'],
            self::FOCUS => ['label' => 'Focus (mouse or touch)', 'look' => 'The field\'s own rule turns to the strong ink and thickens from 1px to 2px. No outer box.', 'hook' => ':focus-within'],
            self::FOCUS_KEYBOARD => ['label' => 'Focus (keyboard)', 'look' => 'As above, plus a 2px ring offset 2px around the control only, never over the label.', 'hook' => ':focus-visible with keyboard modality'],
            self::FILLED => ['label' => 'Filled', 'look' => 'The rule stays strong so an answered field reads as settled.', 'hook' => ':has(:not(:placeholder-shown))'],
            self::INVALID => ['label' => 'Invalid', 'look' => 'Bronze rule (brass on dark) and a message with a warning icon that names the fix.', 'hook' => '[aria-invalid="true"]'],
            self::VALID => ['label' => 'Valid (after a correction)', 'look' => 'Only once an invalid field is corrected: a tick and the success tone.', 'hook' => '[data-corrected]'],
            self::DISABLED => ['label' => 'Disabled', 'look' => 'Dotted rule, muted ink, not-allowed cursor; skipped by Tab.', 'hook' => ':disabled'],
            self::READONLY => ['label' => 'Read-only', 'look' => 'On the sunken surface behind a quiet dotted rule, so it does not invite typing; still focusable and copyable.', 'hook' => '[readonly]'],
            self::LOADING => ['label' => 'Loading', 'look' => 'Muted ink and a hairline that draws slowly along the foot of the field.', 'hook' => '[data-loading]'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }
}
