<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class AuditLogService
{
    public function __construct(private readonly AuditLogRepositoryInterface $logs) {}

    /**
     * @param  array<string, mixed>  $meta
     */
    public function record(?User $actor, string $action, ?Model $subject = null, array $meta = []): AuditLog
    {
        if (! AuditAction::isValid($action)) {
            throw new InvalidArgumentException(sprintf('Unknown audit action "%s".', $action));
        }

        return $this->logs->record($actor?->getKey(), $action, $subject, $meta);
    }

    /**
     * @return Collection<int, AuditLog>
     */
    public function recent(int $limit = 20): Collection
    {
        return $this->logs->recent($limit);
    }

    /**
     * @return Collection<int, AuditLog>
     */
    public function forSubject(Model $subject): Collection
    {
        return $this->logs->forSubject($subject);
    }

    /**
     * @return Collection<int, AuditLog>
     */
    public function withAction(string $action, int $limit = 50): Collection
    {
        return $this->logs->withAction($action, $limit);
    }
}
