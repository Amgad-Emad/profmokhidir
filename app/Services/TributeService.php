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
use InvalidArgumentException;

/**
 * Visitor tributes: submitted as pending, shown only once an admin or editor
 * approves them. Every submission emails the notification address from the
 * site settings, or every moderator (admins and editors) when none is set.
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
        private readonly SettingService $settings,
        private readonly TributeShareCardService $cards,
    ) {
        parent::__construct($tributes, $audit, $cache);
    }

    protected function cacheKey(): string
    {
        return CacheKeys::TRIBUTES;
    }

    protected function cacheKeys(): array
    {
        return [CacheKeys::TRIBUTES, CacheKeys::TRIBUTES.'.latest', CacheKeys::TRIBUTES_FEATURED];
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

        $this->notifyStaff(new TributeAwaitingReview($tribute));

        return $tribute;
    }

    /**
     * Send a staff notice to the configured notification address, or to every
     * moderator when the site settings have none.
     */
    private function notifyStaff(TributeAwaitingReview $notification): void
    {
        $address = $this->settings->notificationAddress();

        if ($address !== null) {
            Notification::route('mail', $address)->notify($notification);

            return;
        }

        Notification::send($this->moderators(), $notification);
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
        $this->cards->forget($tribute);

        $tribute = $this->repository->update($tribute, [
            'status' => TributeStatus::Rejected,
            'approved_by' => null,
            'approved_at' => null,
            'published_at' => null,
            'is_featured' => false,
            'featured_at' => null,
        ]);

        $this->audit->record($actor, AuditAction::TRIBUTE_REJECTED, $tribute, array_filter([
            'name' => $tribute->name,
            'note' => $note,
        ]));
        $this->flushCache();

        return $tribute;
    }

    /**
     * Fix typos in a tribute. The original text is kept in the audit log.
     *
     * @param  array{name?: string, location?: ?string, message?: string}  $data
     */
    public function edit(Tribute $tribute, array $data, User $actor): Tribute
    {
        $original = [
            'name' => $tribute->name,
            'location' => $tribute->location,
            'message' => $tribute->message,
        ];

        $changes = [];

        if (array_key_exists('name', $data)) {
            $changes['name'] = trim((string) $data['name']);
        }

        if (array_key_exists('location', $data)) {
            $changes['location'] = filled($data['location']) ? trim((string) $data['location']) : null;
        }

        if (array_key_exists('message', $data)) {
            $changes['message'] = mb_substr(trim((string) $data['message']), 0, Tribute::MESSAGE_MAX_LENGTH);
        }

        $changes = array_filter($changes, fn ($value, string $key): bool => $value !== $original[$key], ARRAY_FILTER_USE_BOTH);

        if ($changes === []) {
            return $tribute;
        }

        $this->cards->forget($tribute);
        $tribute = $this->repository->update($tribute, $changes);

        $this->audit->record($actor, AuditAction::TRIBUTE_EDITED, $tribute, [
            'name' => $tribute->name,
            'original' => array_intersect_key($original, $changes),
            'changed' => array_keys($changes),
        ]);
        $this->flushCache();

        return $tribute;
    }

    /**
     * Approve several tributes at once; returns how many changed.
     *
     * @param  list<int>  $ids
     */
    public function approveMany(array $ids, User $actor): int
    {
        return $this->moderateMany($ids, $actor, fn (Tribute $tribute) => $this->approve($tribute, $actor), TributeStatus::Approved);
    }

    /**
     * @param  list<int>  $ids
     */
    public function rejectMany(array $ids, User $actor): int
    {
        return $this->moderateMany($ids, $actor, fn (Tribute $tribute) => $this->reject($tribute, $actor), TributeStatus::Rejected);
    }

    public function countByStatus(TributeStatus $status): int
    {
        return $this->tributes()->countByStatus($status);
    }

    /**
     * @param  list<int>  $ids
     * @param  callable(Tribute): Tribute  $action
     */
    private function moderateMany(array $ids, User $actor, callable $action, TributeStatus $target): int
    {
        $count = 0;

        foreach (array_unique(array_map('intval', $ids)) as $id) {
            $tribute = $this->repository->find($id);

            if ($tribute === null || $tribute->status === $target) {
                continue;
            }

            $action($tribute);
            $count++;
        }

        return $count;
    }

    public function delete($model, ?User $actor = null): bool
    {
        $this->cards->forget($model);
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
     * Approved tributes for the public page, a page at a time, in display order,
     * optionally one relationship only. The featured tribute opens the book, so
     * it is left out of the pages.
     *
     * @return LengthAwarePaginator<int, Tribute>
     */
    public function paginateApproved(int $perPage, ?TributeRelationship $relationship = null): LengthAwarePaginator
    {
        $featured = $this->featured();

        return $this->tributes()->paginateApproved($relationship, $perPage, $featured?->getKey());
    }

    /**
     * The approved tribute editors chose to open the book with (cached).
     */
    public function featured(): ?Tribute
    {
        return $this->remember(CacheKeys::TRIBUTES_FEATURED, fn (): ?Tribute => $this->tributes()->featured());
    }

    /**
     * Make an approved tribute the featured one; only one is featured at a time.
     */
    public function feature(Tribute $tribute, User $actor): Tribute
    {
        if (! $tribute->isApproved()) {
            throw new InvalidArgumentException('Only an approved tribute can be featured.');
        }

        $this->tributes()->clearFeatured((int) $tribute->getKey());

        $tribute = $this->repository->update($tribute, ['is_featured' => true, 'featured_at' => now()]);

        $this->audit->record($actor, AuditAction::TRIBUTE_FEATURED, $tribute, ['name' => $tribute->name]);
        $this->flushCache();

        return $tribute;
    }

    public function unfeature(Tribute $tribute, User $actor): Tribute
    {
        if (! $tribute->isFeatured()) {
            return $tribute;
        }

        $tribute = $this->repository->update($tribute, ['is_featured' => false, 'featured_at' => null]);

        $this->audit->record($actor, AuditAction::TRIBUTE_UNFEATURED, $tribute, ['name' => $tribute->name]);
        $this->flushCache();

        return $tribute;
    }

    /**
     * The share card of an approved tribute (drawn once, then cached), or null.
     */
    public function shareCardPath(Tribute $tribute): ?string
    {
        return $this->cards->pathFor($tribute);
    }

    public function shareCardFilename(Tribute $tribute): string
    {
        return $this->cards->filename($tribute);
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
