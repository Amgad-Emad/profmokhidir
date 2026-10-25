<?php

declare(strict_types=1);

namespace App\Constants\PageDefinitions;

use App\Constants\Taglines;
use App\Support\PageBlockDefinition as Block;

/**
 * The editable blocks of the home page, in the order they appear on it.
 * Defaults are the copy the page was built with (App\Constants\Taglines).
 */
final class HomeBlocks
{
    public const string SECTION_HERO = 'Hero';

    public const string SECTION_CHAPTERS = 'A life in chapters';

    public const string SECTION_NUMBERS = 'His work in numbers';

    public const string SECTION_CONTINUE = 'Continue the story';

    public const string SECTION_SEO = 'Search & sharing';

    public const string HERO_HEADING_FIRST = 'Professor';

    public const string HERO_HEADING_SECOND = 'Mohamed Osman Khidir';

    public const string CHAPTERS_INTRO = 'Each decade of his life, drawn from the record of his education, appointments and honours.';

    public const string CHAPTERS_EMPTY = 'The chapters of his life will appear here as his record is catalogued.';

    public const string NUMBERS_EMPTY = 'The figures will appear once his publications, appointments and honours are catalogued.';

    /**
     * @return list<Block>
     */
    public static function definitions(): array
    {
        return [
            Block::shortText('hero_label', 'Hero label', self::SECTION_HERO, Taglines::HERO_LABEL, 30, hint: 'The small label above his name.'),
            Block::shortText('hero_heading_first', 'Hero heading, first line', self::SECTION_HERO, self::HERO_HEADING_FIRST, 30),
            Block::shortText('hero_heading_second', 'Hero heading, second line', self::SECTION_HERO, self::HERO_HEADING_SECOND, 60, hint: 'Set large across the hero: keep it to his name.'),
            Block::shortText('hero_tagline', 'Hero tagline', self::SECTION_HERO, Taglines::HERO_LINE, 160, hint: 'One sentence under his name. It is also the description search engines show.'),
            Block::shortText('hero_epigraph', 'Hero epigraph', self::SECTION_HERO, Taglines::HOME_EPIGRAPH, 140, required: false, hint: 'A short line in his voice under the tagline. Leave empty to show none.'),
            Block::image('hero_image', 'Hero image', self::SECTION_HERO, 'Shown beside his name. Leave empty to use the portrait from About & profile.'),
            Block::link('button_story', 'Button: Read his story', self::SECTION_HERO, Taglines::READ_STORY, 'about', 30),
            Block::link('button_tribute', 'Button: Leave a tribute', self::SECTION_HERO, Taglines::LEAVE_TRIBUTE, 'tributes.create', 30),
            Block::shortText('scroll_cue', 'Scroll cue', self::SECTION_HERO, Taglines::SCROLL_CUE, 20, hint: 'The one word at the foot of the hero.'),

            Block::shortText('chapters_title', 'Chapter title', self::SECTION_CHAPTERS, Taglines::HOME_CHAPTERS, 60),
            Block::longText('chapters_intro', 'Chapter introduction', self::SECTION_CHAPTERS, self::CHAPTERS_INTRO, 240, required: false),
            Block::shortText('chapters_empty', 'Line while there are no chapters', self::SECTION_CHAPTERS, self::CHAPTERS_EMPTY, 160),

            Block::shortText('numbers_title', 'Chapter title', self::SECTION_NUMBERS, Taglines::HOME_NUMBERS, 60),
            Block::shortText('numbers_empty', 'Line while there are no figures', self::SECTION_NUMBERS, self::NUMBERS_EMPTY, 160),

            Block::shortText('continue_title', 'Chapter title', self::SECTION_CONTINUE, Taglines::HOME_CONTINUE, 60),
            Block::shortText('story_about', 'Card line: About', self::SECTION_CONTINUE, Taglines::STORY_ABOUT, 140),
            Block::shortText('story_publications', 'Card line: Publications', self::SECTION_CONTINUE, Taglines::STORY_PUBLICATIONS, 140),
            Block::shortText('story_tributes', 'Card line: Tributes', self::SECTION_CONTINUE, Taglines::STORY_TRIBUTES, 140),
            Block::shortText('closing_line', 'Closing line under the seal', self::SECTION_CONTINUE, Taglines::HOME_CLOSING, 120),

            Block::seo(self::SECTION_SEO, hint: 'Leave the title empty to show his name alone, and the description empty to use the hero tagline.'),
        ];
    }
}
