<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\ChatReport;
use App\Repositories\Contracts\ChatReportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseEloquentRepository<ChatReport>
 */
class EloquentChatReportRepository extends BaseEloquentRepository implements ChatReportRepositoryInterface
{
    public function __construct(ChatReport $report)
    {
        parent::__construct($report);
    }

    public function findFor(int $messageId, int $reporterId): ?ChatReport
    {
        return $this->query()->where('message_id', $messageId)->where('reporter_id', $reporterId)->first();
    }

    public function findWithRelations(int $id): ?ChatReport
    {
        return $this->withRelations($this->query())->find($id);
    }

    public function open(): Collection
    {
        return $this->withRelations($this->query()->open())->orderBy('id')->get();
    }

    public function countOpen(): int
    {
        return $this->query()->open()->count();
    }

    public function paginateByState(bool $open, int $perPage = 20): LengthAwarePaginator
    {
        $query = $open ? $this->query()->open()->orderBy('id') : $this->query()->whereNotNull('resolved_at')->orderByDesc('resolved_at');

        return $this->withRelations($query)->paginate($perPage);
    }

    public function markResolved(ChatReport $report, int $resolvedBy): ChatReport
    {
        $report->forceFill(['resolved_by' => $resolvedBy, 'resolved_at' => now()])->save();

        return $report->refresh();
    }

    public function markResolvedForMessage(int $messageId, int $resolvedBy): int
    {
        return $this->query()->open()->where('message_id', $messageId)->update([
            'resolved_by' => $resolvedBy,
            'resolved_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  Builder<ChatReport>  $query
     * @return Builder<ChatReport>
     */
    private function withRelations(Builder $query): Builder
    {
        return $query->with([
            'message' => fn ($relation) => $relation->withTrashed()->with(['user', 'channel', 'media']),
            'reporter',
            'resolver',
        ]);
    }
}
