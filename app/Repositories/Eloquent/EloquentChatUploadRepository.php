<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\ChatUpload;
use App\Repositories\Contracts\ChatUploadRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseEloquentRepository<ChatUpload>
 */
class EloquentChatUploadRepository extends BaseEloquentRepository implements ChatUploadRepositoryInterface
{
    public function __construct(ChatUpload $upload)
    {
        parent::__construct($upload);
    }

    public function findPendingFor(int $userId, string $uuid): ?ChatUpload
    {
        return $this->query()->with('media')
            ->where('user_id', $userId)
            ->where('uuid', $uuid)
            ->whereNull('claimed_at')
            ->first();
    }

    public function lockPendingFor(int $userId, array $uuids): Collection
    {
        if ($uuids === []) {
            return new Collection;
        }

        $found = $this->query()->with('media')
            ->where('user_id', $userId)
            ->whereIn('uuid', $uuids)
            ->whereNull('claimed_at')
            ->lockForUpdate()
            ->get()
            ->keyBy('uuid');

        return new Collection(array_values(array_filter(array_map(fn (string $uuid) => $found->get($uuid), $uuids))));
    }

    public function countPendingFor(int $userId): int
    {
        return $this->query()->where('user_id', $userId)->whereNull('claimed_at')->count();
    }

    public function markClaimed(ChatUpload $upload, int $messageId, CarbonInterface $at): ChatUpload
    {
        $upload->forceFill(['message_id' => $messageId, 'claimed_at' => $at])->save();

        return $upload;
    }

    public function unclaimedBefore(CarbonInterface $before, int $limit = 500): Collection
    {
        return $this->query()->with('media')
            ->whereNull('claimed_at')
            ->where('created_at', '<', $before)
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }
}
