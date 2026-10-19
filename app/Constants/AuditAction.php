<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Actions recorded in audit_logs. Approvals, rejections, deletions and role
 * changes are always logged.
 */
final class AuditAction
{
    public const string TRIBUTE_APPROVED = 'tribute.approved';

    public const string TRIBUTE_REJECTED = 'tribute.rejected';

    public const string TRIBUTE_DELETED = 'tribute.deleted';

    public const string COMMUNITY_APPLIED = 'community.applied';

    public const string COMMUNITY_APPROVED = 'community.approved';

    public const string COMMUNITY_REJECTED = 'community.rejected';

    public const string COMMUNITY_SUSPENDED = 'community.suspended';

    public const string ROLE_ASSIGNED = 'role.assigned';

    public const string ROLE_REMOVED = 'role.removed';

    public const string ROLES_SYNCED = 'role.synced';

    public const string USER_DELETED = 'user.deleted';

    public const string CONTENT_PUBLISHED = 'content.published';

    public const string CONTENT_UNPUBLISHED = 'content.unpublished';

    public const string CONTENT_DELETED = 'content.deleted';

    public const string MEDIA_DELETED = 'media.deleted';

    public const string CHAT_MESSAGE_DELETED = 'chat.message_deleted';

    public const string CHAT_REPORT_RESOLVED = 'chat.report_resolved';

    public const string ADMIN_LOGIN = 'auth.admin_login';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::TRIBUTE_APPROVED, self::TRIBUTE_REJECTED, self::TRIBUTE_DELETED,
            self::COMMUNITY_APPLIED, self::COMMUNITY_APPROVED, self::COMMUNITY_REJECTED, self::COMMUNITY_SUSPENDED,
            self::ROLE_ASSIGNED, self::ROLE_REMOVED, self::ROLES_SYNCED, self::USER_DELETED,
            self::CONTENT_PUBLISHED, self::CONTENT_UNPUBLISHED, self::CONTENT_DELETED, self::MEDIA_DELETED,
            self::CHAT_MESSAGE_DELETED, self::CHAT_REPORT_RESOLVED, self::ADMIN_LOGIN,
        ];
    }

    public static function isValid(string $action): bool
    {
        return in_array($action, self::all(), true);
    }
}
