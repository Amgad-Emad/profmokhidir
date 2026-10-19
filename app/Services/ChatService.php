<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AuditAction;
use App\Constants\Permissions;
use App\Exceptions\ChatMessageConflictException;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\ChatReport;
use App\Models\User;
use App\Repositories\Contracts\ChatChannelRepositoryInterface;
use App\Repositories\Contracts\ChatMessageRepositoryInterface;
use App\Repositories\Contracts\ChatReadRepositoryInterface;
use App\Repositories\Contracts\ChatReportRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Private community chat. Access is checked here on every call, server side.
 */
class ChatService
{
    public function __construct(
        private readonly ChatChannelRepositoryInterface $channels,
        private readonly ChatMessageRepositoryInterface $messages,
        private readonly ChatReadRepositoryInterface $reads,
        private readonly ChatReportRepositoryInterface $reports,
        private readonly AuditLogService $audit,
        private readonly ConnectionInterface $db,
    ) {}

    // -- Channels -----------------------------------------------------------

    /**
     * @return Collection<int, ChatChannel>
     */
    public function channels(): Collection
    {
        return $this->channels->allOrdered();
    }

    public function findChannel(string $slug): ?ChatChannel
    {
        return $this->channels->findBySlug($slug);
    }

    public function createChannel(string $name, ?string $description = null): ChatChannel
    {
        $base = Str::slug($name) ?: Str::lower(Str::random(8));
        $slug = $base;
        $suffix = 2;

        while ($this->channels->slugExists($slug)) {
            $slug = $base.'-'.$suffix++;
        }

        return $this->channels->create([
            'name' => trim($name),
            'slug' => $slug,
            'description' => filled($description) ? trim((string) $description) : null,
        ]);
    }

    // -- Messages -----------------------------------------------------------

    /**
     * Send a message. Retrying with the same client uuid returns the original
     * message instead of creating a duplicate.
     *
     * @throws AuthorizationException
     */
    public function send(ChatChannel $channel, User $author, string $body, string $clientUuid): ChatMessage
    {
        $this->guardAccess($author);

        $body = $this->cleanBody($body);

        if (! Str::isUuid($clientUuid)) {
            throw new InvalidArgumentException('client_uuid must be a UUID.');
        }

        $existing = $this->messages->findByClientUuid((int) $author->getKey(), $clientUuid);

        if ($existing !== null) {
            return $existing;
        }

        return $this->db->transaction(function () use ($channel, $author, $body, $clientUuid): ChatMessage {
            $message = $this->messages->create([
                'channel_id' => $channel->getKey(),
                'user_id' => $author->getKey(),
                'client_uuid' => $clientUuid,
                'body' => $body,
                'version' => 1,
            ]);

            $this->channels->setLastMessage($channel, (int) $message->getKey());
            $this->reads->markRead((int) $channel->getKey(), (int) $author->getKey(), (int) $message->getKey());

            return $message;
        });
    }

    /**
     * Edit own message with optimistic locking.
     *
     * @throws AuthorizationException
     * @throws ChatMessageConflictException
     */
    public function edit(ChatMessage $message, User $editor, string $body, int $expectedVersion): ChatMessage
    {
        $this->guardAccess($editor);

        if ((int) $message->user_id !== (int) $editor->getKey()) {
            throw new AuthorizationException('Only the author may edit a message.');
        }

        if (! $this->messages->updateBodyIfVersion($message, $this->cleanBody($body), $expectedVersion)) {
            throw ChatMessageConflictException::forVersion($expectedVersion);
        }

        return $message;
    }

    /**
     * Soft-delete a message: the author or a moderator. Always audited.
     *
     * @throws AuthorizationException
     */
    public function delete(ChatMessage $message, User $actor): bool
    {
        $isAuthor = (int) $message->user_id === (int) $actor->getKey();

        if (! $isAuthor && ! $this->canModerate($actor)) {
            throw new AuthorizationException('You may not delete this message.');
        }

        if ($isAuthor) {
            $this->guardAccess($actor);
        }

        return $this->db->transaction(function () use ($message, $actor, $isAuthor): bool {
            $channelId = (int) $message->channel_id;
            $deleted = $this->messages->delete($message);

            $channel = $this->channels->find($channelId);

            if ($channel !== null && (int) $channel->last_message_id === (int) $message->getKey()) {
                $this->channels->setLastMessage($channel, $this->messages->latestIdInChannel($channelId));
            }

            $this->audit->record($actor, AuditAction::CHAT_MESSAGE_DELETED, $message, [
                'channel_id' => $channelId,
                'by_author' => $isAuthor,
            ]);

            return $deleted;
        });
    }

    /**
     * @return Collection<int, ChatMessage>
     */
    public function history(ChatChannel $channel, User $viewer, int $limit = 50, ?int $beforeId = null): Collection
    {
        $this->guardAccess($viewer);

        return $this->messages->latestInChannel((int) $channel->getKey(), $limit, $beforeId);
    }

    /**
     * @return Collection<int, ChatMessage>
     */
    public function since(ChatChannel $channel, User $viewer, int $afterId, int $limit = 100): Collection
    {
        $this->guardAccess($viewer);

        return $this->messages->afterId((int) $channel->getKey(), $afterId, $limit);
    }

    // -- Read state ---------------------------------------------------------

    public function markRead(ChatChannel $channel, User $reader, ?int $messageId = null): void
    {
        $this->guardAccess($reader);

        $messageId ??= $this->messages->latestIdInChannel((int) $channel->getKey());

        if ($messageId !== null) {
            $this->reads->markRead((int) $channel->getKey(), (int) $reader->getKey(), $messageId);
        }
    }

    public function unreadCount(ChatChannel $channel, User $reader): int
    {
        $read = $this->reads->forUser((int) $channel->getKey(), (int) $reader->getKey());

        return $this->messages->countAfter((int) $channel->getKey(), (int) ($read?->last_read_message_id ?? 0));
    }

    // -- Reports ------------------------------------------------------------

    /**
     * Report a message; a second report of the same message by the same user returns the first.
     */
    public function report(ChatMessage $message, User $reporter, ?string $reason = null): ChatReport
    {
        $this->guardAccess($reporter);

        $existing = $this->reports->findFor((int) $message->getKey(), (int) $reporter->getKey());

        if ($existing !== null) {
            return $existing;
        }

        return $this->reports->create([
            'message_id' => $message->getKey(),
            'reporter_id' => $reporter->getKey(),
            'reason' => filled($reason) ? trim((string) $reason) : null,
        ]);
    }

    public function resolveReport(ChatReport $report, User $moderator): ChatReport
    {
        if (! $this->canModerate($moderator)) {
            throw new AuthorizationException('Only moderators may resolve reports.');
        }

        $report = $this->reports->markResolved($report, (int) $moderator->getKey());
        $this->audit->record($moderator, AuditAction::CHAT_REPORT_RESOLVED, $report, ['message_id' => $report->message_id]);

        return $report;
    }

    /**
     * @return Collection<int, ChatReport>
     */
    public function openReports(): Collection
    {
        return $this->reports->open();
    }

    public function countOpenReports(): int
    {
        return $this->reports->countOpen();
    }

    // -- Access -------------------------------------------------------------

    public function canModerate(User $user): bool
    {
        return $user->isAdmin() || $user->can(Permissions::MODERATE_CHAT);
    }

    /**
     * @throws AuthorizationException
     */
    public function guardAccess(User $user): void
    {
        if (! $user->canAccessChat()) {
            throw new AuthorizationException('The community chat is only open to approved members.');
        }
    }

    private function cleanBody(string $body): string
    {
        $body = trim($body);

        if ($body === '') {
            throw new InvalidArgumentException('A message cannot be empty.');
        }

        if (mb_strlen($body) > ChatMessage::BODY_MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf('A message may not exceed %d characters.', ChatMessage::BODY_MAX_LENGTH));
        }

        return $body;
    }
}
