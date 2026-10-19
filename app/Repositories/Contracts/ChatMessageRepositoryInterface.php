<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\ChatMessage;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Chat messages are ordered ONLY by their auto-increment id, never by
 * created_at (two messages in the same second must still have one order for
 * every viewer). Listings include soft-deleted rows so a removed message keeps
 * its place in the conversation as a placeholder.
 *
 * @extends BaseRepositoryInterface<ChatMessage>
 */
interface ChatMessageRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * A user's message by its client-generated uuid (idempotent sends), deleted or not.
     */
    public function findByClientUuid(int $userId, string $clientUuid): ?ChatMessage;

    /**
     * A message by id, deleted or not, with its author.
     */
    public function findWithTrashed(int $id): ?ChatMessage;

    /**
     * Insert a message unless the same user already sent the same client uuid,
     * in which case the existing row is returned. Safe under the unique index
     * when two requests race. Returns the message and whether it was created.
     *
     * @param  array<string, mixed>  $attributes
     * @return array{0: ChatMessage, 1: bool}
     */
    public function firstOrCreateByClientUuid(int $userId, string $clientUuid, array $attributes): array;

    /**
     * Newest messages in a channel in ascending id order, optionally only those before a given id.
     *
     * @return Collection<int, ChatMessage>
     */
    public function latestInChannel(int $channelId, int $limit = 50, ?int $beforeId = null): Collection;

    /**
     * Messages after a given id in ascending id order (the keyset cursor used by polling).
     *
     * @return Collection<int, ChatMessage>
     */
    public function afterId(int $channelId, int $afterId, int $limit = 100): Collection;

    /**
     * Messages up to and including a given id that were edited or deleted at or
     * after a moment (so edits and deletions reach every viewer).
     *
     * @return Collection<int, ChatMessage>
     */
    public function changedSince(int $channelId, DateTimeInterface $since, int $upToId, int $limit = 100): Collection;

    /**
     * Live (not deleted) messages after a given id.
     */
    public function countAfter(int $channelId, int $afterId): int;

    /**
     * Messages (deleted or not) before a given id: whether older history exists.
     */
    public function countBefore(int $channelId, int $beforeId): int;

    public function latestIdInChannel(int $channelId): ?int;

    /**
     * Unread live messages per channel for a user: channel id => count.
     *
     * @return array<int, int>
     */
    public function unreadCountsFor(int $userId): array;

    /**
     * Optimistic-lock update: only applies when the stored version matches and
     * the message is not deleted. Returns true when the row was updated.
     */
    public function updateBodyIfVersion(ChatMessage $message, string $body, int $expectedVersion): bool;
}
