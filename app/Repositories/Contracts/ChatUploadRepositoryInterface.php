<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\ChatUpload;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * The temporary area for chat attachments: files a member has uploaded but
 * not yet sent.
 *
 * @extends BaseRepositoryInterface<ChatUpload>
 */
interface ChatUploadRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * A member's own unclaimed upload by its public uuid.
     */
    public function findPendingFor(int $userId, string $uuid): ?ChatUpload;

    /**
     * A member's unclaimed uploads with these uuids, locked for update so two
     * sends cannot claim the same file. Returned in the order of $uuids.
     *
     * @param  list<string>  $uuids
     * @return Collection<int, ChatUpload>
     */
    public function lockPendingFor(int $userId, array $uuids): Collection;

    public function countPendingFor(int $userId): int;

    public function markClaimed(ChatUpload $upload, int $messageId, CarbonInterface $at): ChatUpload;

    /**
     * Unclaimed uploads created before the given moment (the scheduler prunes them).
     *
     * @return Collection<int, ChatUpload>
     */
    public function unclaimedBefore(CarbonInterface $before, int $limit = 500): Collection;
}
