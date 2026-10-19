<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\AuditLog;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends BaseEloquentRepository<AuditLog>
 */
class EloquentAuditLogRepository extends BaseEloquentRepository implements AuditLogRepositoryInterface
{
    public function __construct(AuditLog $log)
    {
        parent::__construct($log);
    }

    public function record(?int $userId, string $action, ?Model $subject = null, array $meta = []): AuditLog
    {
        return $this->create([
            'user_id' => $userId,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'meta' => $meta === [] ? null : $meta,
            'created_at' => now(),
        ]);
    }

    public function recent(int $limit = 20): Collection
    {
        return $this->query()->with('user')->orderByDesc('id')->limit($limit)->get();
    }

    public function forSubject(Model $subject): Collection
    {
        return $this->query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->orderByDesc('id')
            ->get();
    }

    public function withAction(string $action, int $limit = 50): Collection
    {
        return $this->query()->where('action', $action)->orderByDesc('id')->limit($limit)->get();
    }
}
