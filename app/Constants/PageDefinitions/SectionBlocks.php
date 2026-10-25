<?php

declare(strict_types=1);

namespace App\Constants\PageDefinitions;

use App\Constants\Catalogue;
use App\Constants\Pages;
use App\Constants\Sections;
use App\Constants\Taglines;
use App\Support\PageBlockDefinition as Block;

/**
 * The editable blocks of the section pages that list content (About, CV,
 * Publications, Awards, Videos, Pictures, Teaching, Commentary, Other
 * Information). Every one opens with its heading and intro (the section's
 * title and summary, also used by the menus, cards and search results), then
 * the lines around its listing, then search & sharing.
 */
final class SectionBlocks
{
    public const string SECTION_OPENING = 'Opening';

    public const string SECTION_LISTING = 'Around the listing';

    public const string SECTION_SEO = 'Search & sharing';

    /** Seeded empty: the description is then the intro (About: the short biography). */
    public const string SEO_HINT = 'Leave the title empty to use the page heading, and the description empty to use the intro.';

    /**
     * @return list<Block>
     */
    public static function definitions(string $page): array
    {
        $section = Sections::definitions()[$page];

        return [
            Block::shortText('title', 'Page heading', self::SECTION_OPENING, $section['title'], 60, hint: 'Also its name in breadcrumbs, search results and the cards that lead here.'),
            Block::shortText('intro', 'Introduction', self::SECTION_OPENING, $section['summary'], 240, hint: 'One or two sentences under the heading. Also used in menus, cards and as the search description.'),
            ...self::extra($page),
            Block::seo(self::SECTION_SEO, hint: $page === Pages::ABOUT ? 'Leave the title empty to use the page heading, and the description empty to use the short biography.' : self::SEO_HINT),
        ];
    }

    /**
     * @return list<Block>
     */
    private static function extra(string $page): array
    {
        $listing = self::SECTION_LISTING;

        return match ($page) {
            Pages::ABOUT => [
                Block::shortText('epigraph', 'Epigraph', self::SECTION_OPENING, Taglines::ABOUT_EPIGRAPH, 140, required: false, hint: 'Leave empty to show none.'),
                Block::shortText('empty', 'Line while the biography is not published', $listing, 'The biography is being prepared and will appear here once it is published.', 160),
                Block::shortText('expertise_heading', 'Heading: Expertise', $listing, 'Expertise', 30),
                Block::shortText('facts_heading', 'Heading: Key facts', $listing, 'Key facts', 30),
                Block::shortText('contact_heading', 'Heading: Contact', $listing, 'Contact', 30),
            ],
            Pages::CV => [
                Block::shortText('empty', 'Line while the CV is empty', $listing, 'The curriculum vitae is being compiled and will appear here once it is published.', 160),
                Block::shortText('updated_label', 'Label: Last updated', $listing, 'Last updated', 30),
                Block::shortText('index_label', 'Label above the list of sections', $listing, 'Sections', 30),
                Block::shortText('print_notice', 'Notice printed instead of the CV', $listing, 'The curriculum vitae of :name is published to be read online only, at :url.', 240,
                    hint: 'Only seen on paper: the CV itself is never printed.', tokens: [':name' => 'his name', ':url' => 'the address of the CV page']),
            ],
            Pages::PUBLICATIONS => [
                Block::shortText('selected_label', 'Label: Selected works', $listing, Catalogue::SELECTED_WORKS_LABEL, 40),
                Block::shortText('refine_label', 'Button: Refine the catalogue (small screens)', $listing, 'Refine the catalogue', 40),
                Block::shortText('empty', 'Line while nothing is catalogued', $listing, 'Publications will be listed here as they are catalogued.', 160),
                Block::shortText('none_match', 'Count line when the filters match nothing', $listing, 'No publications match these filters', 80),
                Block::shortText('empty_filtered', 'Line when the filters match nothing', $listing, 'Nothing has been catalogued under these filters yet.', 160),
            ],
            Pages::AWARDS => [
                Block::shortText('empty', 'Line while there are no awards', $listing, 'Awards and recognitions will be listed here once they are published.', 160),
            ],
            Pages::VIDEOS => [
                Block::shortText('empty', 'Line while there are no recordings', $listing, 'Recordings of lectures, interviews and events will appear here once they are published.', 160),
                Block::shortText('featured_label', 'Label: Featured recording', $listing, 'Featured recording', 40),
                Block::shortText('all_label', 'Label: All recordings', $listing, 'All recordings', 40),
                Block::shortText('more_label', 'Recording page: More from this event', $listing, 'More from this event', 40),
                Block::shortText('back_label', 'Recording page: link back', $listing, 'All videos', 40),
            ],
            Pages::PICTURES => [
                Block::shortText('empty', 'Line while there are no albums', $listing, 'Photographs will be gathered into albums here once they are published.', 160),
                Block::shortText('album_empty', 'Album page: line while it has no photographs', $listing, 'This album has no photographs yet.', 160),
                Block::shortText('back_label', 'Album page: link back', $listing, 'All albums', 40),
            ],
            Pages::TEACHING => [
                Block::shortText('empty', 'Line while there are no materials', $listing, 'Teaching materials will appear here as they are prepared for sharing.', 160),
                Block::shortText('members_note', 'Members-only note', $listing, 'Some materials are shared with members of the Scientific Community only.', 200, hint: 'Shown to visitors who are not members.'),
                Block::shortText('members_link', 'Members-only note: link to the community', $listing, 'Apply to join the community', 60),
                Block::shortText('members_after', 'Members-only note: words after the link', $listing, 'to download them.', 80),
                Block::shortText('empty_filtered', 'Line when the filters match nothing', $listing, 'No materials match these filters.', 160),
            ],
            Pages::COMMENTARY => [
                Block::shortText('empty', 'Line while nothing is published', $listing, 'Essays and reflections will be published here in due course.', 160),
                Block::shortText('all_topics', 'Filter: All topics', $listing, 'All topics', 30),
                Block::shortText('empty_topic', 'Line when a topic has nothing', $listing, 'Nothing has been written under this topic yet.', 160),
                Block::shortText('back_label', 'Essay page: button back', $listing, 'All commentary', 40),
            ],
            Pages::OTHER => [
                Block::shortText('empty', 'Line while nothing is published', $listing, 'Further information will be added here as it becomes available.', 160),
                Block::shortText('visit_label', 'Link: Visit', $listing, 'Visit', 30),
                Block::shortText('read_more', 'Link: Read more', $listing, 'Read more', 30),
                Block::shortText('back_label', 'Item page: button back', $listing, 'All other information', 40),
            ],
            default => [],
        };
    }
}
