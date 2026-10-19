<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AuditAction;
use App\Constants\CacheKeys;
use App\Constants\Roles;
use App\Constants\TributeRelationship;
use App\Constants\TributeStatus;
use App\Exceptions\TributeLimitReachedException;
use App\Models\Tribute;
use App\Models\User;
use App\Notifications\TributeAwaitingReview;
use App\Repositories\Contracts\TributeRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Visitor tributes: submitted as pending, shown only once an admin or editor
 * approves them. Every submission emails the moderators (admins and editors).
 * Approvals and rejections are audited.
 *
 * @extends AbstractContentService<Tribute>
 */
class TributeService extends AbstractContentService
{
    /** Submissions allowed per hashed IP within SUBMISSION_WINDOW_MINUTES. */
    public const int SUBMISSION_LIMIT = 3;

    public const int SUBMISSION_WINDOW_MINUTES = 60;

    public function __construct(
        TributeRepositoryInterface $tributes,
        AuditLogService $audit,
        CacheRepository $cache,
        private readonly UserRepositoryInterface $users,
    ) {
        parent::__construct($tributes, $audit, $cache);
    }

    protected function cacheKey(): string
    {
        return CacheKeys::TRIBUTES;
    }

    protected function cacheKeys(): array
    {
        return [CacheKeys::TRIBUTES, CacheKeys::TRIBUTES.'.latest'];
    }

    /**
     * Public submission. The visitor's IP is stored only as a keyed hash, and the
     * moderators are told by email that a tribute is waiting.
     *
     * @param  array{name: string, relationship: string|TributeRelationship, message: string, location?: ?string, email?: ?string}  $data
     */
    public function submit(array $data, ?string $ip = null): Tribute
    {
        $ipHash = $ip === null ? null : $this->hashIp($ip);

        if ($ipHash !== null && $this->tributes()->countRecentFromIp($ipHash, now()->subMinutes(self::SUBMISSION_WINDOW_MINUTES)) >= self::SUBMISSION_LIMIT) {
            throw TributeLimitReachedException::make();
        }

        $relationship = $data['relationship'] instanceof TributeRelationship
            ? $data['relationship']
            : TributeRelationship::from($data['relationship']);

        $tribute = $this->repository->create([
            'name' => trim($data['name']),
            'relationship' => $relationship,
            'location' => filled($data['location'] ?? null) ? trim($data['location']) : null,
            'message' => mb_substr(trim($data['message']), 0, Tribute::MESSAGE_MAX_LENGTH),
            'email' => filled($data['email'] ?? null) ? mb_strtolower(trim($data['email'])) : null,
            'status' => TributeStatus::Pending,
            'ip_hash' => $ipHash,
            'sort_order' => 0,
        ]);

        Notification::send($this->moderators(), new TributeAwaitingReview($tribute));

        return $tribute;
    }

    /**
     * Users who moderate tributes: every admin and editor, each once.
     *
     * @return Collection<int, User>
     */
    public function moderators(): Collection
    {
        $moderators = new Collection;

        foreach (Roles::staff() as $role) {
            $moderators = $moderators->merge($this->users->withRole($role));
        }

        return $moderators->unique('id')->sortBy('name')->values();
    }

    public function approve(Tribute $tribute, User $actor): Tribute
    {
        $now = now();

        $tribute = $this->repository->update($tribute, [
            'status' => TributeStatus::Approved,
            'approved_by' => $actor->getKey(),
            'approved_at' => $now,
            'published_at' => $now,
        ]);

        $this->audit->record($actor, AuditAction::TRIBUTE_APPROVED, $tribute, ['name' => $tribute->name]);
        $this->flushCache();

        return $tribute;
    }

    public function reject(Tribute $tribute, User $actor, ?string $note = null): Tribute
    {
        $tribute = $this->repository->update($tribute, [
            'status' => TributeStatus::Rejected,
            'approved_by' => null,
            'approved_at' => null,
            'published_at' => null,
        ]);

        $this->audit->record($actor, AuditAction::TRIBUTE_REJECTED, $tribute, array_filter([
            'name' => $tribute->name,
            'note' => $note,
        ]));
        $this->flushCache();

        return $tribute;
    }

    public function delete($model, ?User $actor = null): bool
    {
        $this->audit->record($actor, AuditAction::TRIBUTE_DELETED, $model, ['name' => $model->name]);

        $deleted = $this->repository->delete($model);
        $this->flushCache();

        return $deleted;
    }

    /**
     * Approved tributes for the public page (cached, in display order).
     *
     * @return Collection<int, Tribute>
     */
    public function approved(): Collection
    {
        return $this->published();
    }

    /**
     * Approved tributes for the public page, a page at a time, in display order.
     *
     * @return LengthAwarePaginator<int, Tribute>
     */
    public function paginateApproved(int $perPage): LengthAwarePaginator
    {
        return $this->repository->paginatePublished($perPage);
    }

    /**
     * @return Collection<int, Tribute>
     */
    public function latestApproved(int $limit = 3): Collection
    {
        return $this->remember(CacheKeys::TRIBUTES.'.latest', fn (): Collection => $this->tributes()->latestApproved($limit));
    }

    /**
     * @return Collection<int, Tribute>
     */
    public function pending(): Collection
    {
        return $this->tributes()->withStatus(TributeStatus::Pending);
    }

    public function countPending(): int
    {
        return $this->tributes()->countByStatus(TributeStatus::Pending);
    }

    /**
     * @return LengthAwarePaginator<int, Tribute>
     */
    public function paginateByStatus(?string $status = null, int $perPage = 20): LengthAwarePaginator
    {
        $statusEnum = $status !== null && $status !== '' ? TributeStatus::from($status) : null;

        return $this->tributes()->paginateByStatus($statusEnum, $perPage);
    }

    public function hashIp(string $ip): string
    {
        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }

    private function tributes(): TributeRepositoryInterface
    {
        /** @var TributeRepositoryInterface $repository */
        $repository = $this->repository;

        return $repository;
    }
}
