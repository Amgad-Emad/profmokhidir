<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Fixed brand copy used on the public pages. Biography and other editorial text
 * come from the database; only these short tagline-voice lines are fixed.
 */
final class Taglines
{
    public const string HERO_LABEL = 'In honour of';

    public const string HERO_LINE = 'Teacher, scholar and mentor. A life devoted to knowledge and to those who sought it.';

    public const string HOME_QUOTE = 'Scholarship is the one inheritance that grows each time it is shared.';

    public const string READ_STORY = 'Read his story';

    public const string LEAVE_TRIBUTE = 'Leave a tribute';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::HERO_LABEL, self::HERO_LINE, self::HOME_QUOTE, self::READ_STORY, self::LEAVE_TRIBUTE];
    }
}
