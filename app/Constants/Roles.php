<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Role names registered with spatie/laravel-permission. A user becomes a MEMBER
 * only once an admin approves their community application (see CommunityStatus);
 * until then they are an APPLICANT.
 */
final class Roles
{
    public const string ADMIN = 'admin';

    public const string EDITOR = 'editor';

    public const string MEMBER = 'member';

    public const string APPLICANT = 'applicant';

    public const string DEFAULT = self::APPLICANT;

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::ADMIN, self::EDITOR, self::MEMBER, self::APPLICANT];
    }

    /**
     * Roles permitted to use the admin dashboard.
     *
     * @return list<string>
     */
    public static function staff(): array
    {
        return [self::ADMIN, self::EDITOR];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::ADMIN => 'Administrator',
            self::EDITOR => 'Editor',
            self::MEMBER => 'Community member',
            self::APPLICANT => 'Applicant',
        ];
    }

    public static function isValid(string $role): bool
    {
        return in_array($role, self::all(), true);
    }
}
