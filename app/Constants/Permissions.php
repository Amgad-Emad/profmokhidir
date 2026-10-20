<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Permission names registered with spatie/laravel-permission, one per area of
 * the application, and the roles that hold them. Policies check permissions,
 * never role names.
 */
final class Permissions
{
    public const string ACCESS_ADMIN = 'access admin';

    public const string MANAGE_CONTENT = 'manage content';

    public const string MODERATE_TRIBUTES = 'moderate tributes';

    public const string MANAGE_COMMUNITY = 'manage community';

    public const string MODERATE_CHAT = 'moderate chat';

    public const string MANAGE_USERS = 'manage users';

    public const string MANAGE_SETTINGS = 'manage settings';

    public const string ACCESS_CHAT = 'access chat';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::ACCESS_ADMIN,
            self::MANAGE_CONTENT,
            self::MODERATE_TRIBUTES,
            self::MANAGE_COMMUNITY,
            self::MODERATE_CHAT,
            self::MANAGE_USERS,
            self::MANAGE_SETTINGS,
            self::ACCESS_CHAT,
        ];
    }

    /**
     * Permissions held by each role.
     *
     * @return array<string, list<string>>
     */
    public static function matrix(): array
    {
        return [
            Roles::ADMIN => self::all(),
            Roles::EDITOR => [self::ACCESS_ADMIN, self::MANAGE_CONTENT, self::MODERATE_TRIBUTES],
            Roles::MEMBER => [self::ACCESS_CHAT],
            Roles::APPLICANT => [],
        ];
    }

    /**
     * @return list<string>
     */
    public static function forRole(string $role): array
    {
        return self::matrix()[$role] ?? [];
    }

    public static function isValid(string $permission): bool
    {
        return in_array($permission, self::all(), true);
    }
}
