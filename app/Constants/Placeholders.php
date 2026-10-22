<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The marker the seeders put on every piece of placeholder copy, and how the
 * dashboard and the edit screens point it out so it is replaced before launch.
 */
final class Placeholders
{
    /** Text the seeders prefix to placeholder copy. */
    public const string MARKER = '[Placeholder]';

    /** Flag shown beside a field that still holds placeholder copy. */
    public const string FLAG = 'Replace before launch';

    /** Note under the flag, for screen readers and the form summary. */
    public const string FLAG_HINT = 'This field still holds sample text from the site set-up. Write the real text before the site launches.';

    public static function contains(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if (self::contains($item)) {
                    return true;
                }
            }

            return false;
        }

        return is_string($value) && str_contains($value, self::MARKER);
    }
}
