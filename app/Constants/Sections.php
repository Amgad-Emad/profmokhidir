<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The public sections of the site, in navigation order. Every route, nav item,
 * footer link and placeholder page is derived from these definitions.
 */
final class Sections
{
    public const string ABOUT = 'about';

    public const string CV = 'cv';

    public const string PUBLICATIONS = 'publications';

    public const string AWARDS = 'awards';

    public const string VIDEOS = 'videos';

    public const string PICTURES = 'pictures';

    public const string TEACHING = 'teaching';

    public const string COMMENTARY = 'commentary';

    public const string COMMUNITY = 'community';

    public const string OTHER = 'other';

    public const string TRIBUTES = 'tributes';

    /** Shown in the header navigation (and the footer). */
    public const string PLACEMENT_PRIMARY = 'primary';

    /** Shown in the footer and the full-screen menu only. */
    public const string PLACEMENT_FOOTER = 'footer';

    /**
     * Section keys in navigation order.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * @return list<string>
     */
    public static function placements(): array
    {
        return [self::PLACEMENT_PRIMARY, self::PLACEMENT_FOOTER];
    }

    public static function isValid(string $key): bool
    {
        return array_key_exists($key, self::definitions());
    }

    /**
     * Sections the home page's "Continue the story" cards lead to, in order.
     *
     * @return list<string>
     */
    public static function homeStory(): array
    {
        return [self::ABOUT, self::PUBLICATIONS, self::TRIBUTES];
    }

    /**
     * Sections the About page links on to.
     *
     * @return list<string>
     */
    public static function aboutLinks(): array
    {
        return [self::CV, self::PUBLICATIONS, self::AWARDS];
    }

    /**
     * key => label (navigation), title (page heading), path, route name, placement, summary.
     *
     * @return array<string, array{label: string, title: string, path: string, route: string, placement: string, summary: string}>
     */
    public static function definitions(): array
    {
        return [
            self::ABOUT => [
                'label' => 'About',
                'title' => 'About Prof. Mokhidir',
                'path' => '/about',
                'route' => 'about',
                'placement' => self::PLACEMENT_PRIMARY,
                'summary' => 'Biography, portrait, title and affiliation, areas of expertise and key facts.',
            ],
            self::CV => [
                'label' => 'CV & Achievements',
                'title' => 'CV and Achievements',
                'path' => '/cv',
                'route' => 'cv',
                'placement' => self::PLACEMENT_PRIMARY,
                'summary' => 'Education, appointments, honours and milestones, presented on the page.',
            ],
            self::PUBLICATIONS => [
                'label' => 'Publications',
                'title' => 'Publications',
                'path' => '/publications',
                'route' => 'publications',
                'placement' => self::PLACEMENT_PRIMARY,
                'summary' => 'Books, articles and papers by type and year, with links.',
            ],
            self::AWARDS => [
                'label' => 'Awards',
                'title' => 'Awards',
                'path' => '/awards',
                'route' => 'awards',
                'placement' => self::PLACEMENT_PRIMARY,
                'summary' => 'Prizes and recognitions, with the awarding organisation and year.',
            ],
            self::VIDEOS => [
                'label' => 'Videos',
                'title' => 'Videos',
                'path' => '/videos',
                'route' => 'videos',
                'placement' => self::PLACEMENT_PRIMARY,
                'summary' => 'Lectures, interviews and events, with dates and locations.',
            ],
            self::PICTURES => [
                'label' => 'Pictures',
                'title' => 'Pictures',
                'path' => '/pictures',
                'route' => 'pictures',
                'placement' => self::PLACEMENT_PRIMARY,
                'summary' => 'Captioned photographs grouped in albums.',
            ],
            self::TEACHING => [
                'label' => 'Teaching',
                'title' => 'Teaching Materials',
                'path' => '/teaching',
                'route' => 'teaching',
                'placement' => self::PLACEMENT_PRIMARY,
                'summary' => 'Lecture notes and course materials grouped by course, topic and type.',
            ],
            self::COMMENTARY => [
                'label' => 'Commentary',
                'title' => 'Commentary Corner',
                'path' => '/commentary',
                'route' => 'commentary',
                'placement' => self::PLACEMENT_PRIMARY,
                'summary' => 'Essays and reflections, arranged by topic.',
            ],
            self::COMMUNITY => [
                'label' => 'Community',
                'title' => 'Scientific Community',
                'path' => '/community',
                'route' => 'community',
                'placement' => self::PLACEMENT_PRIMARY,
                'summary' => 'A private circle of colleagues and former students. Membership is by application.',
            ],
            self::OTHER => [
                'label' => 'Other Information',
                'title' => 'Other Information',
                'path' => '/other',
                'route' => 'other',
                'placement' => self::PLACEMENT_FOOTER,
                'summary' => 'Further notes and resources that do not belong to another section.',
            ],
            self::TRIBUTES => [
                'label' => 'Tributes',
                'title' => 'Tributes',
                'path' => '/tributes',
                'route' => 'tributes',
                'placement' => self::PLACEMENT_PRIMARY,
                'summary' => 'Messages from students, colleagues and friends, published after review.',
            ],
        ];
    }
}
