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

    /** Epigraph under the hero tagline. */
    public const string HOME_EPIGRAPH = 'Scholarship is the one inheritance that grows each time it is shared.';

    /** The line beneath the seal that closes the home page. */
    public const string HOME_CLOSING = 'His lessons continue in every student he taught.';

    /** The small cue at the foot of the hero. */
    public const string SCROLL_CUE = 'Scroll';

    /** Headings of the home page's narrative sections. */
    public const string HOME_CHAPTERS = 'A life in chapters';

    public const string HOME_NUMBERS = 'His work in numbers';

    public const string HOME_CONTINUE = 'Continue the story';

    /** The one line on each "Continue the story" card. */
    public const string STORY_ABOUT = 'The full biography, his fields of expertise and the facts of a long career.';

    public const string STORY_PUBLICATIONS = 'Books, articles and papers, with citations and links, by year and type.';

    public const string STORY_TRIBUTES = 'Words from his students, colleagues and friends, and a place to add your own.';

    /** Epigraph under the About chapter opening. */
    public const string ABOUT_EPIGRAPH = 'Knowledge is the light we pass on.';

    public const string READ_STORY = 'Read his story';

    public const string LEAVE_TRIBUTE = 'Leave a tribute';

    public const string TRIBUTES_LABEL = 'Remembering';

    public const string TRIBUTES_HEADING = 'He taught us how to ask the right questions.';

    public const string TRIBUTE_THANKS = 'Thank you. Your tribute will appear once it has been reviewed.';

    /**
     * Section key (Sections::homeStory()) => the line on its home card.
     *
     * @return array<string, string>
     */
    public static function storyLines(): array
    {
        return [
            Sections::ABOUT => self::STORY_ABOUT,
            Sections::PUBLICATIONS => self::STORY_PUBLICATIONS,
            Sections::TRIBUTES => self::STORY_TRIBUTES,
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::HERO_LABEL, self::HERO_LINE, self::HOME_EPIGRAPH, self::HOME_CLOSING, self::SCROLL_CUE,
            self::HOME_CHAPTERS, self::HOME_NUMBERS, self::HOME_CONTINUE, self::STORY_ABOUT, self::STORY_PUBLICATIONS, self::STORY_TRIBUTES, self::ABOUT_EPIGRAPH, self::READ_STORY, self::LEAVE_TRIBUTE,
            self::TRIBUTES_LABEL, self::TRIBUTES_HEADING, self::TRIBUTE_THANKS,
        ];
    }
}
