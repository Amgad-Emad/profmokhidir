<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * User roles. Community applicants are tracked separately via CommunityStatus;
 * a user only becomes a MEMBER once an admin approves their application.
 */
final class Roles
{
    public const string ADMIN = 'admin';

    public const string EDITOR = 'editor';

    public const string MEMBER = 'member';

    public const string DEFAULT = self::MEMBER;

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::ADMIN, self::EDITOR, self::MEMBER];
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
        ];
    }

    public static function isValid(string $role): bool
    {
        return in_array($role, self::all(), true);
    }
}
