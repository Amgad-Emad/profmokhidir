<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\ChatMessage;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<ChatMessage>
 */
interface ChatMessageRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * A user's message by its client-generated uuid (idempotent sends).
     */
    public function findByClientUuid(int $userId, string $clientUuid): ?ChatMessage;

    /**
     * Newest messages in a channel in ascending id order, optionally only those before a given id.
     *
     * @return Collection<int, ChatMessage>
     */
    public function latestInChannel(int $channelId, int $limit = 50, ?int $beforeId = null): Collection;

    /**
     * Messages after a given id in ascending order (polling).
     *
     * @return Collection<int, ChatMessage>
     */
    public function afterId(int $channelId, int $afterId, int $limit = 100): Collection;

    public function countAfter(int $channelId, int $afterId): int;

    public function latestIdInChannel(int $channelId): ?int;

    /**
     * Optimistic-lock update: only applies when the stored version matches.
     * Returns true when the row was updated.
     */
    public function updateBodyIfVersion(ChatMessage $message, string $body, int $expectedVersion): bool;
}
