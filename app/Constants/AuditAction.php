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

    public const string USER_INVITED = 'user.invited';

    public const string USER_INVITE_RESENT = 'user.invite_resent';

    public const string USER_DEACTIVATED = 'user.deactivated';

    public const string USER_REACTIVATED = 'user.reactivated';

    public const string SETTINGS_UPDATED = 'settings.updated';

    public const string CONTENT_CREATED = 'content.created';

    public const string CONTENT_RESTORED = 'content.restored';

    public const string CONTENT_PUBLISHED = 'content.published';

    public const string CONTENT_UNPUBLISHED = 'content.unpublished';

    public const string CONTENT_DELETED = 'content.deleted';

    public const string CONTENT_PURGED = 'content.purged';

    public const string TRIBUTE_EDITED = 'tribute.edited';

    public const string TRIBUTE_FEATURED = 'tribute.featured';

    public const string TRIBUTE_UNFEATURED = 'tribute.unfeatured';

    public const string TRIBUTE_REOPENED = 'tribute.reopened';

    public const string MEDIA_UPLOADED = 'media.uploaded';

    public const string MEDIA_UPDATED = 'media.updated';

    public const string MEDIA_DELETED = 'media.deleted';

    public const string CHAT_MESSAGE_DELETED = 'chat.message_deleted';

    public const string CHAT_MESSAGE_REPORTED = 'chat.message_reported';

    public const string CHAT_REPORT_RESOLVED = 'chat.report_resolved';

    public const string CHAT_MESSAGE_PINNED = 'chat.message_pinned';

    public const string CHAT_MESSAGE_UNPINNED = 'chat.message_unpinned';

    public const string ADMIN_LOGIN = 'auth.admin_login';

    /** Page copy (Admin › Pages): the editor's draft put live, or the page returned to its built-in copy. */
    public const string PAGE_PUBLISHED = 'page.published';

    public const string PAGE_UNPUBLISHED = 'page.unpublished';

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
            self::USER_INVITED, self::USER_INVITE_RESENT, self::USER_DEACTIVATED, self::USER_REACTIVATED, self::SETTINGS_UPDATED,
            self::CONTENT_CREATED, self::CONTENT_RESTORED, self::CONTENT_PUBLISHED, self::CONTENT_UNPUBLISHED, self::CONTENT_DELETED, self::CONTENT_PURGED,
            self::TRIBUTE_EDITED, self::TRIBUTE_FEATURED, self::TRIBUTE_UNFEATURED, self::TRIBUTE_REOPENED, self::MEDIA_UPLOADED, self::MEDIA_UPDATED, self::MEDIA_DELETED,
            self::CHAT_MESSAGE_DELETED, self::CHAT_MESSAGE_REPORTED, self::CHAT_REPORT_RESOLVED,
            self::CHAT_MESSAGE_PINNED, self::CHAT_MESSAGE_UNPINNED, self::ADMIN_LOGIN, self::PAGE_PUBLISHED, self::PAGE_UNPUBLISHED,
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
            self::TRIBUTE_APPROVED => __('approved a tribute'),
            self::TRIBUTE_REJECTED => __('rejected a tribute'),
            self::TRIBUTE_DELETED => __('deleted a tribute'),
            self::COMMUNITY_APPLIED => __('applied to join the community'),
            self::COMMUNITY_APPROVED => __('approved a community application'),
            self::COMMUNITY_REJECTED => __('rejected a community application'),
            self::COMMUNITY_SUSPENDED => __('suspended a community member'),
            self::COMMUNITY_REINSTATED => __('reinstated a community member'),
            self::COMMUNITY_REMOVED => __('removed a community member'),
            self::ROLE_ASSIGNED => __('assigned a role'),
            self::ROLE_REMOVED => __('removed a role'),
            self::ROLES_SYNCED => __('changed roles'),
            self::USER_DELETED => __('deleted a user'),
            self::USER_INVITED => __('invited a staff member'),
            self::USER_INVITE_RESENT => __('resent a staff invitation'),
            self::USER_DEACTIVATED => __('deactivated an account'),
            self::USER_REACTIVATED => __('reactivated an account'),
            self::SETTINGS_UPDATED => __('updated the site settings'),
            self::CONTENT_CREATED => __('created content'),
            self::CONTENT_RESTORED => __('restored content from the trash'),
            self::CONTENT_PUBLISHED => __('published content'),
            self::CONTENT_UNPUBLISHED => __('unpublished content'),
            self::CONTENT_DELETED => __('deleted content'),
            self::CONTENT_PURGED => __('deleted content permanently'),
            self::TRIBUTE_EDITED => __('edited a tribute'),
            self::TRIBUTE_FEATURED => __('featured a tribute'),
            self::TRIBUTE_UNFEATURED => __('removed a tribute from the featured place'),
            self::TRIBUTE_REOPENED => __('returned a tribute to the review queue'),
            self::MEDIA_UPLOADED => __('uploaded a file'),
            self::MEDIA_UPDATED => __('updated image details'),
            self::MEDIA_DELETED => __('deleted a file'),
            self::CHAT_MESSAGE_DELETED => __('deleted a chat message'),
            self::CHAT_MESSAGE_REPORTED => __('reported a chat message'),
            self::CHAT_REPORT_RESOLVED => __('resolved a chat report'),
            self::CHAT_MESSAGE_PINNED => __('pinned a chat message'),
            self::CHAT_MESSAGE_UNPINNED => __('unpinned a chat message'),
            self::ADMIN_LOGIN => __('signed in'),
            self::PAGE_PUBLISHED => __('updated the wording of a page'),
            self::PAGE_UNPUBLISHED => __('returned a page to its original wording'),
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
