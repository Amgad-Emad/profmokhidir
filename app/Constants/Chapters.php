<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The five chapters of the masthead. Each groups sections from
 * App\Constants\Sections (the item names live there) and may add an action
 * (a link that is not a section, such as leaving a tribute or opening the chat)
 * and a featured item drawn from the database for the right-hand column.
 */
final class Chapters
{
    public const string LIFE = 'life';

    public const string WORK = 'work';

    public const string ARCHIVE = 'archive';

    public const string COMMUNITY = 'community';

    public const string TRIBUTES = 'tributes';

    /** Featured item kinds. */
    public const string FEATURED_PORTRAIT = 'portrait';

    public const string FEATURED_PUBLICATION = 'publication';

    public const string FEATURED_ALBUM = 'album';

    public const string FEATURED_TRIBUTE = 'tribute';

    /** Actions: links that are not sections. */
    public const string ACTION_LEAVE_TRIBUTE = 'leave-tribute';

    public const string ACTION_SIGN_IN = 'sign-in';

    public const string ACTION_OPEN_CHAT = 'open-chat';

    /**
     * Chapter keys in masthead order.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::definitions());
    }

    public static function isValid(string $key): bool
    {
        return array_key_exists($key, self::definitions());
    }

    /**
     * @return list<string>
     */
    public static function featuredKinds(): array
    {
        return [self::FEATURED_PORTRAIT, self::FEATURED_PUBLICATION, self::FEATURED_ALBUM, self::FEATURED_TRIBUTE];
    }

    /**
     * key => label (the word in the bar), strap (one line under the chapter name in the
     * panel), sections (Sections keys, in order), actions (action keys appended after the
     * sections), featured (a featured kind, or null when the chapter has no featured item).
     *
     * @return array<string, array{label: string, strap: string, sections: list<string>, actions: list<string>, featured: string|null}>
     */
    public static function definitions(): array
    {
        return [
            self::LIFE => [
                'label' => 'Life',
                'strap' => 'The man, his path and his honours.',
                'sections' => [Sections::ABOUT, Sections::CV, Sections::AWARDS, Sections::OTHER],
                'actions' => [],
                'featured' => self::FEATURED_PORTRAIT,
            ],
            self::WORK => [
                'label' => 'Work',
                'strap' => 'What he wrote, taught and argued.',
                'sections' => [Sections::PUBLICATIONS, Sections::TEACHING, Sections::COMMENTARY],
                'actions' => [],
                'featured' => self::FEATURED_PUBLICATION,
            ],
            self::ARCHIVE => [
                'label' => 'Archive',
                'strap' => 'Photographs and recordings from six decades.',
                'sections' => [Sections::PICTURES, Sections::VIDEOS],
                'actions' => [],
                'featured' => self::FEATURED_ALBUM,
            ],
            self::COMMUNITY => [
                'label' => 'Community',
                'strap' => 'Colleagues and students, in conversation.',
                'sections' => [Sections::COMMUNITY],
                'actions' => [self::ACTION_SIGN_IN, self::ACTION_OPEN_CHAT],
                'featured' => null,
            ],
            self::TRIBUTES => [
                'label' => 'Tributes',
                'strap' => 'A book of remembrance, open to all.',
                'sections' => [Sections::TRIBUTES],
                'actions' => [self::ACTION_LEAVE_TRIBUTE],
                'featured' => self::FEATURED_TRIBUTE,
            ],
        ];
    }

    /**
     * One line under each section link in the panels (Hanken 300). Section names come
     * from Sections::definitions(); these are shorter than the section summaries.
     *
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        return [
            Sections::ABOUT => 'Biography, portrait and the facts of a life in science.',
            Sections::CV => 'Education, appointments and honours, set out on the page.',
            Sections::AWARDS => 'Prizes and recognitions, with organisation and year.',
            Sections::OTHER => 'Further notes and resources.',
            Sections::PUBLICATIONS => 'Books, articles and papers by type and year.',
            Sections::TEACHING => 'Lecture notes and course materials by course and topic.',
            Sections::COMMENTARY => 'Essays and reflections, arranged by topic.',
            Sections::PICTURES => 'Captioned photographs in albums.',
            Sections::VIDEOS => 'Lectures, interviews and events.',
            Sections::COMMUNITY => 'A private circle of colleagues and former students.',
            Sections::TRIBUTES => 'Messages from students, colleagues and friends.',
        ];
    }

    /**
     * Actions: label, description, route name and who sees them ("guest", "member" or "all").
     *
     * @return array<string, array{label: string, description: string, route: string, audience: string}>
     */
    public static function actions(): array
    {
        return [
            self::ACTION_LEAVE_TRIBUTE => [
                'label' => Masthead::LEAVE_TRIBUTE,
                'description' => 'Your own words, published after review.',
                'route' => 'tributes.create',
                'audience' => self::AUDIENCE_ALL,
            ],
            self::ACTION_SIGN_IN => [
                'label' => Masthead::SIGN_IN,
                'description' => 'For approved members of the circle.',
                'route' => 'login',
                'audience' => self::AUDIENCE_GUEST,
            ],
            self::ACTION_OPEN_CHAT => [
                'label' => Masthead::OPEN_CHAT,
                'description' => 'The members\' room, for those admitted.',
                'route' => 'community.chat',
                'audience' => self::AUDIENCE_MEMBER,
            ],
        ];
    }

    public const string AUDIENCE_ALL = 'all';

    public const string AUDIENCE_GUEST = 'guest';

    public const string AUDIENCE_MEMBER = 'member';

    /**
     * Label above each featured item.
     *
     * @return array<string, string>
     */
    public static function featuredLabels(): array
    {
        return [
            self::FEATURED_PORTRAIT => 'In honour of',
            self::FEATURED_PUBLICATION => 'Latest publication',
            self::FEATURED_ALBUM => 'Newest album',
            self::FEATURED_TRIBUTE => 'Featured tribute',
        ];
    }
}
