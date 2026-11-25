<?php

declare(strict_types=1);

namespace App\Constants;

use InvalidArgumentException;

/**
 * The public pages whose words and images editors change in Admin › Pages,
 * one row each in the `pages` table, in the order the admin lists them.
 * Section pages share their key with App\Constants\Sections. "Header &
 * footer" holds what every page shares; "Error pages" the five error screens.
 */
final class Pages
{
    /**
     * Page => the content type whose records the page lists, shown inside the
     * page's own editor (Admin › Pages › that page) under the page's words.
     */
    public const array RECORDS = [
        self::CV => ContentTypes::CV_ENTRY,
        self::PUBLICATIONS => ContentTypes::PUBLICATION,
        self::AWARDS => ContentTypes::AWARD,
        self::VIDEOS => ContentTypes::VIDEO,
        self::PICTURES => ContentTypes::ALBUM,
        self::TEACHING => ContentTypes::TEACHING_MATERIAL,
        self::COMMENTARY => ContentTypes::COMMENTARY,
        self::OTHER => ContentTypes::OTHER_ITEM,
    ];

    public const string HOME = 'home';

    public const string ABOUT = Sections::ABOUT;

    public const string CV = Sections::CV;

    public const string PUBLICATIONS = Sections::PUBLICATIONS;

    public const string AWARDS = Sections::AWARDS;

    public const string VIDEOS = Sections::VIDEOS;

    public const string PICTURES = Sections::PICTURES;

    public const string TEACHING = Sections::TEACHING;

    public const string COMMENTARY = Sections::COMMENTARY;

    public const string COMMUNITY = Sections::COMMUNITY;

    public const string TRIBUTES = Sections::TRIBUTES;

    public const string OTHER = Sections::OTHER;

    public const string PRIVACY = 'privacy';

    public const string ERRORS = 'errors';

    public const string NAVIGATION = 'navigation';

    /** Route parameter of the edit screen. */
    public const string ROUTE_PARAMETER = 'page';

    /**
     * Pages that show only what an editor entered, in each language: an empty field shows
     * nothing (no built-in copy, no English in place of missing Arabic) and the editor
     * shows no placeholders.
     */
    public const array ENTERED_ONLY = [self::HOME, self::ABOUT];

    /** Admin routes: the list, one page's edit screen, and an error page drawn with its drafts. */
    public const string INDEX_ROUTE = 'admin.content.pages';

    public const string EDIT_ROUTE = 'admin.content.pages.edit';

    public const string ERROR_PREVIEW_ROUTE = 'admin.content.pages.error-preview';

    /**
     * key => admin title, one line on what it holds, the public route it is
     * previewed on (null for the error pages, previewed in the dashboard) and
     * the section it opens (null for pages that are not sections).
     *
     * @return array<string, array{title: string, summary: string, route: string|null, section: string|null}>
     */
    public static function definitions(): array
    {
        return [
            self::HOME => self::page('Home', 'The hero, the chapter titles, the story cards and the closing line.', 'home'),
            self::ABOUT => self::page('About', 'The heading, epigraph and small headings around the biography.', 'about', Sections::ABOUT),
            self::CV => self::page('CV and Achievements', 'The heading, intro and labels of the CV ledger.', 'cv', Sections::CV),
            self::PUBLICATIONS => self::page('Publications', 'The heading, intro and catalogue labels.', 'publications', Sections::PUBLICATIONS),
            self::AWARDS => self::page('Awards', 'The heading and intro of the roll of honour.', 'awards', Sections::AWARDS),
            self::VIDEOS => self::page('Videos', 'The heading, intro and recording labels.', 'videos', Sections::VIDEOS),
            self::PICTURES => self::page('Pictures', 'The heading, intro and album labels.', 'pictures', Sections::PICTURES),
            self::TEACHING => self::page('Teaching Materials', 'The heading, intro and the members-only note.', 'teaching', Sections::TEACHING),
            self::COMMENTARY => self::page('Commentary Corner', 'The heading, intro and topic labels.', 'commentary', Sections::COMMENTARY),
            self::COMMUNITY => self::page('Scientific Community', 'What the community is, who may join, the conduct rules, the privacy note and every application message.', 'community', Sections::COMMUNITY),
            self::TRIBUTES => self::page('Tributes', 'The book of remembrance, the tribute form and the thank-you page.', 'tributes', Sections::TRIBUTES),
            self::OTHER => self::page('Other Information', 'The heading, intro and link labels.', 'other', Sections::OTHER),
            self::PRIVACY => self::page('Privacy', 'How the site counts its readers (shown while visitor analytics is on).', Analytics::PRIVACY_ROUTE),
            self::ERRORS => self::page('Error pages', 'The heading and line of each error page.', null),
            self::NAVIGATION => self::page('Header & footer', 'Chapter names and order, menu lines, footer links and the lines every page shares.', 'home'),
        ];
    }

    /**
     * @return list<string>
     */
    public static function showsOnlyEntered(string $key): bool
    {
        return in_array($key, self::ENTERED_ONLY, true);
    }

    public static function all(): array
    {
        return array_keys(self::definitions());
    }

    public static function isValid(string $key): bool
    {
        return array_key_exists($key, self::definitions());
    }

    /**
     * @return array{title: string, summary: string, route: string|null, section: string|null}
     */
    public static function get(string $key): array
    {
        return self::definitions()[$key] ?? throw new InvalidArgumentException(sprintf('Unknown page "%s".', $key));
    }

    /**
     * The page whose search & sharing block applies on a route: a page's own
     * route only, never its detail pages (they describe their record).
     */
    public static function forRoute(?string $route): ?string
    {
        if ($route === null) {
            return null;
        }

        foreach (self::definitions() as $key => $page) {
            if ($page['route'] === $route && $key !== self::NAVIGATION) {
                return $key;
            }
        }

        return null;
    }

    /**
     * The page that holds a section's heading and intro.
     */
    public static function forSection(string $section): ?string
    {
        foreach (self::definitions() as $key => $page) {
            if ($page['section'] === $section) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @return array{title: string, summary: string, route: string|null, section: string|null}
     */
    private static function page(string $title, string $summary, ?string $route, ?string $section = null): array
    {
        return ['title' => $title, 'summary' => $summary, 'route' => $route, 'section' => $section];
    }
}
