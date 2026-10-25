<?php

declare(strict_types=1);

namespace App\Constants\PageDefinitions;

use App\Constants\Chapters;
use App\Constants\ErrorPages;
use App\Constants\Masthead;
use App\Constants\Pages;
use App\Constants\Sections;
use App\Constants\Taglines;
use App\Support\PageBlockDefinition as Block;

/**
 * The editable blocks every page shares (the header and the footer), the
 * privacy page and the error pages. Navigation keeps its structure: the five
 * chapters can be renamed and reordered and the footer links chosen from the
 * list of pages, but sections cannot move between chapters.
 */
final class SiteBlocks
{
    public const string SECTION_HEADER = 'Header';

    public const string SECTION_CHAPTERS = 'Chapters';

    public const string SECTION_SECTIONS = 'Section names and menu lines';

    public const string SECTION_MENU = 'Menu extras';

    public const string SECTION_FOOTER = 'Footer';

    public const string SECTION_SHARE = 'Default share image';

    public const string FOOTER_HEADING = 'Sections';

    public const string FOOTER_DOMAIN = 'profmokhidir.com';

    public const string COPYRIGHT = '© :year Professor Mohamed Osman Khidir. All rights reserved.';

    public const int FOOTER_LINKS_MAX = 16;

    public const string PRIVACY_LABEL = 'Privacy';

    public const string PRIVACY_HEADING = 'How this site counts its readers';

    public const string PRIVACY_DESCRIPTION = 'How this site counts its readers: first-party, without cookies, without personal data.';

    /** The privacy page as it was written, with the live numbers as placeholders. */
    public const string PRIVACY_BODY = '<p>To learn which pages are read, this site keeps its own simple visitor count. It is run by the site itself: no other company sees it, and nothing is shared or sold.</p>'
        .'<h2>What is counted</h2><ul>'
        .'<li>The page opened, and the site it was reached from (only its name, such as a search engine).</li>'
        .'<li>How long the page was read: the time the tab was in view while you scrolled or used the page within the last :idle_seconds seconds, and how far down you scrolled.</li>'
        .'<li>The country you are in, worked out from your internet address, which is then forgotten. Nothing more precise than the country is kept.</li>'
        .'<li>Whether you use a phone, tablet or computer, and the name of the browser.</li></ul>'
        .'<h2>What is not</h2><ul>'
        .'<li>No cookies are set and nothing is stored in your browser for this count.</li>'
        .'<li>No internet address, name, email or other personal detail is stored. Visits on the same day are told apart by a scrambled code that changes every day and cannot be turned back into anything about you.</li>'
        .'<li>If your browser asks sites not to track you (Do Not Track or Global Privacy Control), nothing is counted at all.</li></ul>'
        .'<h2>How long it is kept</h2>'
        .'<p>The record of each page view is deleted after :raw_days days. After that only daily totals remain (for example, how many people read a page on a given day), and those are deleted after :rollup_months months.</p>';

    /**
     * @return list<Block>
     */
    public static function definitions(string $page): array
    {
        return match ($page) {
            Pages::NAVIGATION => self::navigation(),
            Pages::PRIVACY => self::privacy(),
            Pages::ERRORS => self::errors(),
            default => [],
        };
    }

    /**
     * @return list<Block>
     */
    private static function navigation(): array
    {
        $chapters = [];

        foreach (Chapters::definitions() as $key => $chapter) {
            $chapters[] = ['key' => $key, 'label' => $chapter['label'], 'strap' => $chapter['strap']];
        }

        $sections = [];
        $footer = [];

        foreach (Sections::definitions() as $key => $section) {
            $sections[] = ['key' => $key, 'label' => $section['label'], 'line' => Chapters::descriptions()[$key] ?? $section['summary']];
            $footer[] = ['label' => $section['label'], 'route' => $section['route']];
        }

        $actions = [];

        foreach (Chapters::actions() as $key => $action) {
            $actions[] = ['key' => $key, 'label' => $action['label'], 'line' => $action['description']];
        }

        $featured = [];

        foreach (Chapters::featuredLabels() as $key => $label) {
            $featured[] = ['key' => $key, 'label' => $label];
        }

        return [
            Block::shortText('utility_line', 'Line above the header', self::SECTION_HEADER, Masthead::UTILITY_LINE, 80),
            Block::shortText('header_tribute', 'Header button: Leave a tribute', self::SECTION_HEADER, Masthead::LEAVE_TRIBUTE, 30),

            Block::list('chapters', 'Chapters', self::SECTION_CHAPTERS, $chapters, [
                'key' => Block::fixedField('Chapter'),
                'label' => Block::field('Name in the header', 16),
                'strap' => Block::field('Line in its panel', 70),
            ], 'Chapter', fixedItems: true, hint: 'The five words across the header, in this order. Keep each to one short word: the header has room for little more.'),

            Block::list('sections', 'Sections', self::SECTION_SECTIONS, $sections, [
                'key' => Block::fixedField('Section'),
                'label' => Block::field('Name', 30),
                'line' => Block::field('Line in the menu', 90),
            ], 'Section', fixedItems: true, sortable: false, hint: 'Each section\'s name in the menus and above its page heading, and its line in the chapter panels. Their order sets the chapter numerals, so it is fixed.'),

            Block::list('actions', 'Menu links', self::SECTION_MENU, $actions, [
                'key' => Block::fixedField('Link'),
                'label' => Block::field('Label', 30),
                'line' => Block::field('Line in the menu', 70),
            ], 'Link', fixedItems: true, sortable: false),
            Block::list('featured_labels', 'Labels above the featured items', self::SECTION_MENU, $featured, [
                'key' => Block::fixedField('Item'),
                'label' => Block::field('Label', 30),
            ], 'Label', fixedItems: true, sortable: false),

            Block::shortText('footer_line', 'Footer closing line', self::SECTION_FOOTER, Taglines::HOME_CLOSING, 120),
            Block::shortText('footer_line_home', 'Footer closing line on the home page', self::SECTION_FOOTER, Taglines::ABOUT_EPIGRAPH, 120, hint: 'The home page already ends with the closing line above its seal.'),
            Block::shortText('footer_heading', 'Label above the footer links', self::SECTION_FOOTER, self::FOOTER_HEADING, 30),
            Block::list('footer_links', 'Footer links', self::SECTION_FOOTER, $footer, [
                'label' => Block::field('Label', 30),
                'route' => Block::field('Goes to', 60, Block::FIELD_ROUTE),
            ], 'Link', maxItems: self::FOOTER_LINKS_MAX, hint: 'Each link goes to a page chosen from the list, so none can break.'),
            Block::shortText('footer_domain', 'Domain in the footer', self::SECTION_FOOTER, self::FOOTER_DOMAIN, 40),
            Block::shortText('copyright', 'Copyright line', self::SECTION_FOOTER, self::COPYRIGHT, 120, tokens: [':year' => 'the current year']),

            Block::seo(self::SECTION_SHARE, hint: 'The image every page shares on social media unless its own is set. Leave empty to use the branded card.', text: false),
        ];
    }

    /**
     * @return list<Block>
     */
    private static function privacy(): array
    {
        return [
            Block::shortText('label', 'Label', 'Opening', self::PRIVACY_LABEL, 30, hint: 'Also the name of the footer link.'),
            Block::shortText('heading', 'Page heading', 'Opening', self::PRIVACY_HEADING, 80),
            Block::longText('body', 'The privacy text', 'Text', self::PRIVACY_BODY, 4000, inline: false, tokens: [
                ':idle_seconds' => 'how many idle seconds end a reading',
                ':raw_days' => 'how many days page views are kept',
                ':rollup_months' => 'how many months daily totals are kept',
            ], hint: 'Headings and lists are allowed. The numbers stay in step with the analytics settings through the placeholders.'),
            Block::seo('Search & sharing', self::PRIVACY_LABEL, self::PRIVACY_DESCRIPTION),
        ];
    }

    /**
     * @return list<Block>
     */
    private static function errors(): array
    {
        $blocks = [];

        foreach (ErrorPages::all() as $code => $copy) {
            $section = sprintf('Error %d: %s', $code, ErrorPages::describe($code));
            $blocks[] = Block::shortText('title_'.$code, 'Heading', $section, $copy['title'], 40);
            $blocks[] = Block::shortText('line_'.$code, 'Line', $section, $copy['line'], 120);
        }

        return [
            ...$blocks,
            Block::shortText('label', 'Label before the code', 'Shared by every error page', ErrorPages::LABEL_PREFIX, 20, hint: 'For example "Error 404".'),
            Block::shortText('search_label', 'Search field label', 'Shared by every error page', ErrorPages::SEARCH_LABEL, 40),
            Block::shortText('chapters_label', 'Label above the chapters', 'Shared by every error page', ErrorPages::CHAPTERS_LABEL, 40),
            Block::shortText('home_button', 'Button home', 'Shared by every error page', ErrorPages::HOME_LABEL, 30),
        ];
    }
}
