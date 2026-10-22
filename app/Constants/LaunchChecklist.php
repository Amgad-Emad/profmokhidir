<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The "Before launch" checklist on the dashboard: what must be true before the
 * site goes public. Each check is computed from the database by
 * LaunchReadinessService and links to the screen that fixes it.
 */
final class LaunchChecklist
{
    public const string PORTRAIT = 'portrait';

    public const string CONTACT = 'contact';

    public const string PROFILE_PUBLISHED = 'profile-published';

    public const string PLACEHOLDERS = 'placeholders';

    public const string PUBLISHED = 'published';

    /**
     * Sections that must each have at least one published item, keyed by
     * admin navigation item, with the content type that fills them.
     *
     * @return array<string, string>
     */
    public static function sections(): array
    {
        return [
            AdminNavigation::CV => ContentTypes::CV_ENTRY,
            AdminNavigation::PUBLICATIONS => ContentTypes::PUBLICATION,
            AdminNavigation::AWARDS => ContentTypes::AWARD,
            AdminNavigation::VIDEOS => ContentTypes::VIDEO,
            AdminNavigation::ALBUMS => ContentTypes::ALBUM,
            AdminNavigation::TEACHING => ContentTypes::TEACHING_MATERIAL,
            AdminNavigation::COMMENTARY => ContentTypes::COMMENTARY,
            AdminNavigation::OTHER => ContentTypes::OTHER_ITEM,
        ];
    }

    /**
     * Content types searched for placeholder copy, in checklist order.
     *
     * @return list<string>
     */
    public static function placeholderTypes(): array
    {
        return [
            ContentTypes::PROFILE,
            ContentTypes::CV_ENTRY,
            ContentTypes::PUBLICATION,
            ContentTypes::AWARD,
            ContentTypes::VIDEO,
            ContentTypes::ALBUM,
            ContentTypes::TEACHING_MATERIAL,
            ContentTypes::COMMENTARY,
            ContentTypes::OTHER_ITEM,
            ContentTypes::HOME_QUOTE,
        ];
    }
}
