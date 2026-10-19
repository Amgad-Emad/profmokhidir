<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\ChatMessage;
use App\Repositories\Contracts\ChatMessageRepositoryInterface;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;

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
        return $this->query()->withTrashed()->with('user')
            ->where('user_id', $userId)->where('client_uuid', $clientUuid)->first();
    }

    public function findWithTrashed(int $id): ?ChatMessage
    {
        return $this->query()->withTrashed()->with('user')->find($id);
    }

    public function firstOrCreateByClientUuid(int $userId, string $clientUuid, array $attributes): array
    {
        $existing = $this->findByClientUuid($userId, $clientUuid);

        if ($existing !== null) {
            return [$existing, false];
        }

        try {
            $message = $this->query()->create($attributes + ['user_id' => $userId, 'client_uuid' => $clientUuid]);
        } catch (UniqueConstraintViolationException) {
            // Lost the race against a retry of the same send: return that one.
            $message = $this->findByClientUuid($userId, $clientUuid);

            if ($message === null) {
                throw new \RuntimeException('Duplicate client uuid reported but the message cannot be found.');
            }

            return [$message, false];
        }

        $message->load('user');

        return [$message, true];
    }

    public function latestInChannel(int $channelId, int $limit = 50, ?int $beforeId = null): Collection
    {
        $messages = $this->query()->withTrashed()->with('user')
            ->where('channel_id', $channelId)
            ->when($beforeId !== null, fn ($query) => $query->where('id', '<', $beforeId))
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return $messages->reverse()->values();
    }

    public function afterId(int $channelId, int $afterId, int $limit = 100): Collection
    {
        return $this->query()->withTrashed()->with('user')
            ->where('channel_id', $channelId)
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function changedSince(int $channelId, DateTimeInterface $since, int $upToId, int $limit = 100): Collection
    {
        // ">=" rather than ">" because timestamps have whole-second precision; a
        // change made in the same second as the previous poll must not be lost.
        // Clients apply a change twice harmlessly (same id, same version).
        return $this->query()->withTrashed()->with('user')
            ->where('channel_id', $channelId)
            ->where('id', '<=', $upToId)
            ->where('updated_at', '>=', $since)
            ->where(fn ($query) => $query->whereNotNull('deleted_at')->orWhereNotNull('edited_at'))
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function countAfter(int $channelId, int $afterId): int
    {
        return $this->query()->where('channel_id', $channelId)->where('id', '>', $afterId)->count();
    }

    public function countBefore(int $channelId, int $beforeId): int
    {
        return $this->query()->withTrashed()->where('channel_id', $channelId)->where('id', '<', $beforeId)->count();
    }

    public function latestIdInChannel(int $channelId): ?int
    {
        $id = $this->query()->where('channel_id', $channelId)->max('id');

        return $id === null ? null : (int) $id;
    }

    public function unreadCountsFor(int $userId): array
    {
        $rows = $this->model->getConnection()
            ->table($this->model->getTable().' as m')
            ->leftJoin('chat_reads as r', function ($join) use ($userId): void {
                $join->on('r.channel_id', '=', 'm.channel_id')->where('r.user_id', '=', $userId);
            })
            ->whereNull('m.deleted_at')
            ->whereColumn('m.id', '>', $this->model->getConnection()->raw('COALESCE(r.last_read_message_id, 0)'))
            ->groupBy('m.channel_id')
            ->selectRaw('m.channel_id as channel_id, COUNT(*) as unread')
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $counts[(int) $row->channel_id] = (int) $row->unread;
        }

        return $counts;
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
