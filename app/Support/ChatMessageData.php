<?php

declare(strict_types=1);

namespace App\Support;

use App\Constants\ChatCopy;
use App\Constants\ChatSettings;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The one shape in which a chat message leaves the server for the browser.
 * Bodies are rendered to allowlisted HTML here, deleted messages carry only a
 * placeholder, a reply carries a plain excerpt of the message it answers, and
 * times are UTC ISO-8601 for the viewer's clock to format.
 */
final class ChatMessageData
{
    /**
     * @return array{
     *     id: int, client_uuid: string, user_id: int, author: string, affiliation: ?string,
     *     body: string, html: string, created_at: string, updated_at: string, edited_at: ?string,
     *     version: int, deleted: bool, mine: bool, can_edit: bool, can_delete: bool, can_report: bool,
     *     reply_to: ?array{id: int, author: string, excerpt: string, deleted: bool},
     *     pinned: bool, pinned_at: ?string, can_pin: bool
     * }
     */
    public static function from(ChatMessage $message, User $viewer, bool $moderator = false): array
    {
        $deleted = $message->trashed();
        $mine = (int) $message->user_id === (int) $viewer->getKey();
        $author = $message->relationLoaded('user') ? $message->user : null;

        return [
            'id' => (int) $message->getKey(),
            'client_uuid' => (string) $message->client_uuid,
            'user_id' => (int) $message->user_id,
            'author' => $author?->name ?? 'Former member',
            'affiliation' => $author?->affiliation,
            'body' => $deleted ? '' : (string) $message->body,
            'html' => $deleted ? '' : ChatText::toHtml((string) $message->body),
            'created_at' => $message->created_at?->toIso8601ZuluString() ?? now()->toIso8601ZuluString(),
            'updated_at' => $message->updated_at?->toIso8601ZuluString() ?? now()->toIso8601ZuluString(),
            'edited_at' => $deleted ? null : $message->edited_at?->toIso8601ZuluString(),
            'version' => (int) $message->version,
            'deleted' => $deleted,
            'mine' => $mine,
            'can_edit' => ! $deleted && $mine && $viewer->canAccessChat(),
            'can_delete' => ! $deleted && ($moderator || ($mine && $viewer->canAccessChat())),
            'can_report' => ! $deleted && ! $mine && $viewer->canAccessChat(),
            'reply_to' => $deleted ? null : self::replyTo($message),
            'pinned' => ! $deleted && $message->isPinned(),
            'pinned_at' => $deleted ? null : $message->pinned_at?->toIso8601ZuluString(),
            'can_pin' => ! $deleted && $moderator,
        ];
    }

    /**
     * @param  iterable<int, ChatMessage>  $messages
     * @return list<array<string, mixed>>
     */
    public static function collection(iterable $messages, User $viewer, bool $moderator = false): array
    {
        return Collection::make($messages)
            ->map(fn (ChatMessage $message): array => self::from($message, $viewer, $moderator))
            ->values()
            ->all();
    }

    /**
     * A pinned message as the side panel lists it.
     *
     * @return array{id: int, author: string, excerpt: string, created_at: string, pinned_at: ?string, pinned_by: ?string}
     */
    public static function pin(ChatMessage $message): array
    {
        return [
            'id' => (int) $message->getKey(),
            'author' => $message->user?->name ?? 'Former member',
            'excerpt' => ChatText::excerpt((string) $message->body, ChatSettings::PIN_EXCERPT_LENGTH),
            'created_at' => $message->created_at?->toIso8601ZuluString() ?? now()->toIso8601ZuluString(),
            'pinned_at' => $message->pinned_at?->toIso8601ZuluString(),
            'pinned_by' => $message->relationLoaded('pinnedBy') ? $message->pinnedBy?->name : null,
        ];
    }

    public static function removedPlaceholder(): string
    {
        return ChatSettings::REMOVED_PLACEHOLDER;
    }

    /**
     * @return array{id: int, author: string, excerpt: string, deleted: bool}|null
     */
    private static function replyTo(ChatMessage $message): ?array
    {
        if ($message->reply_to_id === null) {
            return null;
        }

        $original = $message->replyTo;

        if ($original === null) {
            return ['id' => (int) $message->reply_to_id, 'author' => '', 'excerpt' => ChatCopy::REPLY_REMOVED, 'deleted' => true];
        }

        $removed = $original->trashed();

        return [
            'id' => (int) $original->getKey(),
            'author' => $removed ? '' : ($original->user?->name ?? 'Former member'),
            'excerpt' => $removed ? ChatCopy::REPLY_REMOVED : ChatText::excerpt((string) $original->body),
            'deleted' => $removed,
        ];
    }
}
