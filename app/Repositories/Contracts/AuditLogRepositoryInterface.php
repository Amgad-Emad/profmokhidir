<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends BaseRepositoryInterface<AuditLog>
 */
interface AuditLogRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function record(?int $userId, string $action, ?Model $subject = null, array $meta = []): AuditLog;

    /**
     * @return Collection<int, AuditLog>
     */
    public function recent(int $limit = 20): Collection;

    /**
     * @return Collection<int, AuditLog>
     */
    public function forSubject(Model $subject): Collection;

    /**
     * @return Collection<int, AuditLog>
     */
    public function withAction(string $action, int $limit = 50): Collection;
}
