<?php

declare(strict_types=1);

namespace App\Support;

use App\Constants\ChatSettings;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The one shape in which a chat message leaves the server for the browser.
 * Bodies are escaped (and auto-linked) here, deleted messages carry only a
 * placeholder, and times are UTC ISO-8601 for the viewer's clock to format.
 */
final class ChatMessageData
{
    /**
     * @return array{
     *     id: int, client_uuid: string, user_id: int, author: string, affiliation: ?string,
     *     body: string, html: string, created_at: string, updated_at: string, edited_at: ?string,
     *     version: int, deleted: bool, mine: bool, can_edit: bool, can_delete: bool, can_report: bool
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

    public static function removedPlaceholder(): string
    {
        return ChatSettings::REMOVED_PLACEHOLDER;
    }
}
