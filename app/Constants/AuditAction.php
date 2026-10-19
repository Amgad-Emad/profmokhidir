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

    public const string COMMUNITY_REINSTATED = 'community.reinstated';

    public const string COMMUNITY_REMOVED = 'community.removed';

    public const string ROLE_ASSIGNED = 'role.assigned';

    public const string ROLE_REMOVED = 'role.removed';

    public const string ROLES_SYNCED = 'role.synced';

    public const string USER_DELETED = 'user.deleted';

    public const string CONTENT_CREATED = 'content.created';

    public const string CONTENT_RESTORED = 'content.restored';

    public const string CONTENT_PUBLISHED = 'content.published';

    public const string CONTENT_UNPUBLISHED = 'content.unpublished';

    public const string CONTENT_DELETED = 'content.deleted';

    public const string TRIBUTE_EDITED = 'tribute.edited';

    public const string MEDIA_UPLOADED = 'media.uploaded';

    public const string MEDIA_UPDATED = 'media.updated';

    public const string MEDIA_DELETED = 'media.deleted';

    public const string CHAT_MESSAGE_DELETED = 'chat.message_deleted';

    public const string CHAT_MESSAGE_REPORTED = 'chat.message_reported';

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
            self::COMMUNITY_REINSTATED, self::COMMUNITY_REMOVED,
            self::ROLE_ASSIGNED, self::ROLE_REMOVED, self::ROLES_SYNCED, self::USER_DELETED,
            self::CONTENT_CREATED, self::CONTENT_RESTORED, self::CONTENT_PUBLISHED, self::CONTENT_UNPUBLISHED, self::CONTENT_DELETED,
            self::TRIBUTE_EDITED, self::MEDIA_UPLOADED, self::MEDIA_UPDATED, self::MEDIA_DELETED,
            self::CHAT_MESSAGE_DELETED, self::CHAT_MESSAGE_REPORTED, self::CHAT_REPORT_RESOLVED, self::ADMIN_LOGIN,
        ];
    }

    /**
     * Human-readable description of each action, for the activity feed.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::TRIBUTE_APPROVED => 'approved a tribute',
            self::TRIBUTE_REJECTED => 'rejected a tribute',
            self::TRIBUTE_DELETED => 'deleted a tribute',
            self::COMMUNITY_APPLIED => 'applied to join the community',
            self::COMMUNITY_APPROVED => 'approved a community application',
            self::COMMUNITY_REJECTED => 'rejected a community application',
            self::COMMUNITY_SUSPENDED => 'suspended a community member',
            self::COMMUNITY_REINSTATED => 'reinstated a community member',
            self::COMMUNITY_REMOVED => 'removed a community member',
            self::ROLE_ASSIGNED => 'assigned a role',
            self::ROLE_REMOVED => 'removed a role',
            self::ROLES_SYNCED => 'changed roles',
            self::USER_DELETED => 'deleted a user',
            self::CONTENT_CREATED => 'created content',
            self::CONTENT_RESTORED => 'restored content from the trash',
            self::CONTENT_PUBLISHED => 'published content',
            self::CONTENT_UNPUBLISHED => 'unpublished content',
            self::CONTENT_DELETED => 'deleted content',
            self::TRIBUTE_EDITED => 'edited a tribute',
            self::MEDIA_UPLOADED => 'uploaded a file',
            self::MEDIA_UPDATED => 'updated image details',
            self::MEDIA_DELETED => 'deleted a file',
            self::CHAT_MESSAGE_DELETED => 'deleted a chat message',
            self::CHAT_MESSAGE_REPORTED => 'reported a chat message',
            self::CHAT_REPORT_RESOLVED => 'resolved a chat report',
            self::ADMIN_LOGIN => 'signed in',
        ];
    }

    public static function label(string $action): string
    {
        return self::labels()[$action] ?? str_replace(['.', '_'], ' ', $action);
    }

    public static function isValid(string $action): bool
    {
        return in_array($action, self::all(), true);
    }
}
