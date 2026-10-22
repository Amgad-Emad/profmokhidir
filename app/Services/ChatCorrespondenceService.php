<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AuditAction;
use App\Constants\ChatChange;
use App\Constants\ChatSettings;
use App\Events\ChatMessageChanged;
use App\Exceptions\ChatMessageRemovedException;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\User;
use App\Repositories\Contracts\ChatMessageRepositoryInterface;
use App\Support\ChatAccess;
use App\Support\ChatMessageData;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;

/**
 * The correspondence-room features around the message stream: pins (moderators
 * only, audited), search within a channel and the members list for the side
 * panel. Access is checked here on every call, as in ChatService.
 */
class ChatCorrespondenceService
{
    public function __construct(
        private readonly ChatMessageRepositoryInterface $messages,
        private readonly AuditLogService $audit,
        private readonly CommunityService $community,
    ) {}

    /**
     * Pin a live message to the channel's side panel. Pinning twice is a no-op.
     *
     * @throws AuthorizationException
     * @throws ChatMessageRemovedException
     */
    public function pin(ChatMessage $message, User $moderator): ChatMessage
    {
        return $this->setPin($message, $moderator, true);
    }

    /**
     * @throws AuthorizationException
     * @throws ChatMessageRemovedException
     */
    public function unpin(ChatMessage $message, User $moderator): ChatMessage
    {
        return $this->setPin($message, $moderator, false);
    }

    private function setPin(ChatMessage $message, User $moderator, bool $pinned): ChatMessage
    {
        ChatAccess::guardModerator($moderator);

        $current = $this->messages->findWithTrashed((int) $message->getKey());

        if ($current === null || $current->trashed()) {
            throw ChatMessageRemovedException::make();
        }

        if ($current->isPinned() === $pinned) {
            return $current;
        }

        $current = $this->messages->setPinned($current, $pinned ? (int) $moderator->getKey() : null);

        $this->audit->record($moderator, $pinned ? AuditAction::CHAT_MESSAGE_PINNED : AuditAction::CHAT_MESSAGE_UNPINNED, $current, [
            'channel_id' => (int) $current->channel_id,
        ]);

        ChatMessageChanged::dispatch((int) $current->channel_id, (int) $current->getKey(), $pinned ? ChatChange::Pinned : ChatChange::Unpinned);

        return $current;
    }

    /**
     * The channel's pinned messages, most recent pin first, as the panel shows them.
     *
     * @return list<array<string, mixed>>
     */
    public function pins(ChatChannel $channel, User $viewer): array
    {
        ChatAccess::guard($viewer);

        return $this->messages->pinned((int) $channel->getKey())
            ->map(fn (ChatMessage $message): array => ChatMessageData::pin($message))
            ->values()
            ->all();
    }

    /**
     * Search within one channel: every word must match (as a prefix). Short or
     * empty terms return nothing.
     *
     * @return array{term: string, messages: list<array<string, mixed>>}
     */
    public function search(ChatChannel $channel, User $viewer, string $term): array
    {
        ChatAccess::guard($viewer);

        $term = Str::limit(trim(preg_replace('/\s+/u', ' ', $term) ?? ''), ChatSettings::SEARCH_MAX_LENGTH, '');

        if (mb_strlen($term) < ChatSettings::SEARCH_MIN_LENGTH) {
            return ['term' => $term, 'messages' => []];
        }

        $messages = $this->messages->search((int) $channel->getKey(), $term, ChatSettings::SEARCH_LIMIT);

        return [
            'term' => $term,
            'messages' => ChatMessageData::collection($messages, $viewer, ChatAccess::canModerate($viewer)),
        ];
    }

    /**
     * Approved members, by name, for the side panel.
     *
     * @return list<array{id: int, name: string, affiliation: ?string}>
     */
    public function members(): array
    {
        return $this->community->members()
            ->sortBy(fn (User $user): string => mb_strtolower($user->name), SORT_NATURAL)
            ->map(fn (User $user): array => [
                'id' => (int) $user->getKey(),
                'name' => $user->name,
                'affiliation' => $user->affiliation,
            ])
            ->values()
            ->all();
    }
}
