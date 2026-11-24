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

    /** The home sections an editor may hide from visitors (search & sharing is not a visible section). */
    public const array HIDEABLE = [self::SECTION_HERO, self::SECTION_CHAPTERS, self::SECTION_NUMBERS, self::SECTION_CONTINUE];

    public const string HERO_HEADING_FIRST = 'Professor';

    public const string HERO_HEADING_SECOND = 'Mohamed Osman Khidir';

    public const string CHAPTERS_INTRO = 'His honours, decade by decade: the prizes, medals and distinctions of a life in science.';

    /** The introduction before the chapters became a record of his honours (replaced on import when unedited). */
    public const string CHAPTERS_INTRO_BEFORE_HONOURS = 'Each decade of his life, drawn from the record of his education, appointments and honours.';

    /** The list blocks that hold the home page's own chapters, figures and cards (edited here, not drawn from other screens). */
    public const string CHAPTER_ITEMS = 'chapter_items';

    public const string FIGURES = 'figures';

    public const string STORY_CARDS = 'story_cards';

    /** Milestones a chapter holds (m1_*, m2_*, m3_*). */
    public const int MILESTONES = 3;

    /**
     * The fields of one chapter: its years, its milestones, a photo and the quote that follows it.
     *
     * @return array<string, array{label: string, kind: string, max: int|null, required: bool}>
     */
    public static function chapterFields(): array
    {
        $fields = [
            Block::HIDDEN => Block::hiddenField('Hide this chapter'),
            'numeral' => Block::inline(Block::field('Numeral', 10, Block::FIELD_PLAIN, false)),
            'years' => Block::inline(Block::field('Years', 30, Block::FIELD_PLAIN, false)),
            'hide_numeral' => Block::field('Hide the numeral', 1, Block::FIELD_TOGGLE, false),
        ];

        for ($n = 1; $n <= self::MILESTONES; $n++) {
            $group = 'Milestone '.$n;
            // Milestones after the first stay out of the editor while empty, behind "Add milestone".
            $field = fn (array $spec): array => $n > 1 ? Block::addable($spec, 'milestone') : $spec;
            $fields['m'.$n.'_year'] = $field(Block::field('Year', 20, Block::FIELD_PLAIN, false, $group));
            $fields['m'.$n.'_title'] = $field(Block::field('Title', 200, Block::FIELD_TEXT, false, $group));
            $fields['m'.$n.'_label'] = $field(Block::field('Kind', 40, Block::FIELD_TEXT, false, $group));
            $fields['m'.$n.'_detail'] = $field(Block::field('Details', 200, Block::FIELD_TEXT, false, $group));
        }

        return $fields + [
            'media_id' => Block::imageField('Image', 'Photo'),
            'alt_text' => Block::field('Alt text', 255, Block::FIELD_TEXT, false, 'Photo'),
            'caption' => Block::field('Caption', 255, Block::FIELD_TEXT, false, 'Photo'),
            'place' => Block::field('Place', 120, Block::FIELD_TEXT, false, 'Photo'),
            'year' => Block::field('Year', 10, Block::FIELD_PLAIN, false, 'Photo'),
            'quote' => Block::field('Quote', 400, Block::FIELD_TEXT, false, 'Quote after this chapter'),
            'quote_credit' => Block::field('Who said it', 120, Block::FIELD_TEXT, false, 'Quote after this chapter'),
        ];
    }

    /**
     * @return list<Block>
     */
    public static function definitions(): array
    {
        return [
            Block::shortText('hero_label', 'Hero label', self::SECTION_HERO, Taglines::HERO_LABEL, 30, hint: 'The small label above his name.', required: false),
            Block::shortText('hero_heading_first', 'Hero heading, first line', self::SECTION_HERO, self::HERO_HEADING_FIRST, 30, required: false),
            Block::shortText('hero_heading_second', 'Hero heading, second line', self::SECTION_HERO, self::HERO_HEADING_SECOND, 60, hint: 'Set large across the hero: keep it to his name.', required: false),
            Block::shortText('hero_tagline', 'Hero tagline', self::SECTION_HERO, Taglines::HERO_LINE, 160, hint: 'One sentence under his name. It is also the description search engines show.', required: false),
            Block::shortText('hero_epigraph', 'Hero epigraph', self::SECTION_HERO, Taglines::HOME_EPIGRAPH, 140, required: false, hint: 'A short line in his voice under the tagline. Leave empty to show none.'),
            Block::image('hero_image', 'Hero image', self::SECTION_HERO, 'Shown beside his name. Leave empty to use the portrait from About & profile.'),
            Block::link('button_story', 'Button: Read his story', self::SECTION_HERO, Taglines::READ_STORY, 'about', 30, required: false),
            Block::link('button_tribute', 'Button: Leave a tribute', self::SECTION_HERO, Taglines::LEAVE_TRIBUTE, 'tributes.create', 30, required: false),
            Block::shortText('scroll_cue', 'Scroll cue', self::SECTION_HERO, Taglines::SCROLL_CUE, 20, hint: 'The one word at the foot of the hero.', required: false),

            Block::shortText('chapters_title', 'Chapter title', self::SECTION_CHAPTERS, Taglines::HOME_CHAPTERS, 60, required: false),
            Block::longText('chapters_intro', 'Chapter introduction', self::SECTION_CHAPTERS, self::CHAPTERS_INTRO, 240, required: false),
            Block::list(self::CHAPTER_ITEMS, 'Chapters', self::SECTION_CHAPTERS, [], self::chapterFields(), 'Chapter', maxItems: 12, hint: 'Each chapter as it shows on the home page, in order: its years, up to three milestones, a photo and an optional quote shown after it. Leave the numeral empty to show none.'),

            Block::shortText('numbers_title', 'Chapter title', self::SECTION_NUMBERS, Taglines::HOME_NUMBERS, 60, required: false),
            Block::list(self::FIGURES, 'Figures', self::SECTION_NUMBERS, [], [
                Block::HIDDEN => Block::hiddenField('Hide this figure'),
                'value' => Block::field('Number', 12, Block::FIELD_PLAIN),
                'label' => Block::field('Label', 60),
            ], 'Figure', maxItems: 6, hint: 'Each figure as it shows on the home page: the number and the words under it.'),

            Block::shortText('continue_title', 'Chapter title', self::SECTION_CONTINUE, Taglines::HOME_CONTINUE, 60, required: false),
            Block::list(self::STORY_CARDS, 'Cards', self::SECTION_CONTINUE, [], [
                Block::HIDDEN => Block::hiddenField('Hide this card'),
                'title' => Block::field('Title', 60),
                'numeral' => Block::field('Numeral', 10, Block::FIELD_PLAIN, false),
                'line' => Block::field('Line', 160, Block::FIELD_TEXT, false),
                'route' => Block::field('Goes to', 60, Block::FIELD_ROUTE),
                'media_id' => Block::imageField('Image'),
            ], 'Card', maxItems: 6, hint: 'Each card as it shows on the home page: its title, line, the page it opens and its image.'),
            Block::shortText('closing_line', 'Closing line under the seal', self::SECTION_CONTINUE, Taglines::HOME_CLOSING, 120, required: false),

            Block::seo(self::SECTION_SEO, hint: 'Leave the title empty to show his name alone, and the description empty to use the hero tagline.'),
        ];
    }
}
