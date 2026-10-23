<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Constants\Search;
use App\Models\ChatMessage;
use App\Repositories\Contracts\ChatMessageRepositoryInterface;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * @extends BaseEloquentRepository<ChatMessage>
 */
class EloquentChatMessageRepository extends BaseEloquentRepository implements ChatMessageRepositoryInterface
{
    /** Loaded with every listing: the author, the answered message with its author, and the attached files. */
    private const array RELATIONS = ['user', 'replyTo.user', 'media'];

    public function __construct(ChatMessage $message)
    {
        parent::__construct($message);
    }

    public function findByClientUuid(int $userId, string $clientUuid): ?ChatMessage
    {
        return $this->query()->withTrashed()->with(self::RELATIONS)
            ->where('user_id', $userId)->where('client_uuid', $clientUuid)->first();
    }

    public function findWithTrashed(int $id): ?ChatMessage
    {
        return $this->query()->withTrashed()->with(self::RELATIONS)->find($id);
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

        $message->load(self::RELATIONS);

        return [$message, true];
    }

    public function latestInChannel(int $channelId, int $limit = 50, ?int $beforeId = null): Collection
    {
        $messages = $this->query()->withTrashed()->with(self::RELATIONS)
            ->where('channel_id', $channelId)
            ->when($beforeId !== null, fn ($query) => $query->where('id', '<', $beforeId))
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return $messages->reverse()->values();
    }

    public function afterId(int $channelId, int $afterId, int $limit = 100): Collection
    {
        return $this->query()->withTrashed()->with(self::RELATIONS)
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
        return $this->query()->withTrashed()->with(self::RELATIONS)
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

    public function search(int $channelId, string $term, int $limit = 20): Collection
    {
        preg_match_all('/[\p{L}\p{N}]+(?:[\'’][\p{L}]+)?/u', $term, $matches);
        $tokens = array_values(array_unique(array_map(fn (string $word): string => mb_strtolower($word), $matches[0])));

        if ($tokens === []) {
            return new Collection;
        }

        $indexed = array_values(array_filter($tokens, fn (string $token): bool => Search::isIndexed($token)));
        $short = array_values(array_diff($tokens, $indexed));

        return $this->query()->with(self::RELATIONS)
            ->where('channel_id', $channelId)
            ->where(function (Builder $query) use ($indexed, $short): void {
                if ($indexed !== []) {
                    $boolean = implode(' ', array_map(fn (string $token): string => '+'.str_replace(["'", '’'], '', $token).'*', $indexed));
                    $query->whereFullText('body', $boolean, ['mode' => 'boolean']);
                }

                foreach ($short as $token) {
                    $query->where('body', 'like', '%'.addcslashes($token, '%_\\').'%');
                }
            })
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function pinned(int $channelId): Collection
    {
        return $this->query()->with([...self::RELATIONS, 'pinnedBy'])
            ->where('channel_id', $channelId)
            ->whereNotNull('pinned_at')
            ->orderByDesc('pinned_at')
            ->orderByDesc('id')
            ->get();
    }

    public function setPinned(ChatMessage $message, ?int $byUserId): ChatMessage
    {
        $message->timestamps = false;
        $message->forceFill([
            'pinned_at' => $byUserId === null ? null : now(),
            'pinned_by' => $byUserId,
        ])->save();
        $message->timestamps = true;

        return $message->refresh()->load([...self::RELATIONS, 'pinnedBy']);
    }
}
