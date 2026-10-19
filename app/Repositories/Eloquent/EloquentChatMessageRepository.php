<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\ChatMessage;
use App\Repositories\Contracts\ChatMessageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseEloquentRepository<ChatMessage>
 */
class EloquentChatMessageRepository extends BaseEloquentRepository implements ChatMessageRepositoryInterface
{
    public function __construct(ChatMessage $message)
    {
        parent::__construct($message);
    }

    public function findByClientUuid(int $userId, string $clientUuid): ?ChatMessage
    {
        return $this->query()->withTrashed()
            ->where('user_id', $userId)->where('client_uuid', $clientUuid)->first();
    }

    public function latestInChannel(int $channelId, int $limit = 50, ?int $beforeId = null): Collection
    {
        $messages = $this->query()->with('user')
            ->where('channel_id', $channelId)
            ->when($beforeId !== null, fn ($query) => $query->where('id', '<', $beforeId))
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return $messages->reverse()->values();
    }

    public function afterId(int $channelId, int $afterId, int $limit = 100): Collection
    {
        return $this->query()->with('user')
            ->where('channel_id', $channelId)
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function countAfter(int $channelId, int $afterId): int
    {
        return $this->query()->where('channel_id', $channelId)->where('id', '>', $afterId)->count();
    }

    public function latestIdInChannel(int $channelId): ?int
    {
        $id = $this->query()->where('channel_id', $channelId)->max('id');

        return $id === null ? null : (int) $id;
    }

    public function updateBodyIfVersion(ChatMessage $message, string $body, int $expectedVersion): bool
    {
        $updated = $this->query()
            ->whereKey($message->getKey())
            ->where('version', $expectedVersion)
            ->update([
                'body' => $body,
                'version' => $expectedVersion + 1,
                'edited_at' => now(),
                'updated_at' => now(),
            ]);

        if ($updated === 1) {
            $message->refresh();

            return true;
        }

        return false;
    }
}
