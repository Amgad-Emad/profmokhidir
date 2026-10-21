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

    /** Epigraph under the About chapter opening. */
    public const string ABOUT_EPIGRAPH = 'Knowledge is the light we pass on.';

    public const string READ_STORY = 'Read his story';

    public const string LEAVE_TRIBUTE = 'Leave a tribute';

    public const string TRIBUTES_LABEL = 'Remembering';

    public const string TRIBUTES_HEADING = 'He taught us how to ask the right questions.';

    public const string TRIBUTE_THANKS = 'Thank you. Your tribute will appear once it has been reviewed.';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::HERO_LABEL, self::HERO_LINE, self::HOME_QUOTE, self::ABOUT_EPIGRAPH, self::READ_STORY, self::LEAVE_TRIBUTE,
            self::TRIBUTES_LABEL, self::TRIBUTES_HEADING, self::TRIBUTE_THANKS,
        ];
    }
}
