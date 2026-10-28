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

    public function filter(?string $term, array $criteria, int $perPage = 20, bool $oldestFirst = false): LengthAwarePaginator
    {
        return $this->withRelations($this->filtered($term, $criteria))
            ->orderBy('id', $oldestFirst ? 'asc' : 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function countFiltered(?string $term, array $criteria): int
    {
        return $this->filtered($term, $criteria)->count();
    }

    /**
     * @param  array<string, string|list<string>>  $criteria
     * @return Builder<ChatReport>
     */
    private function filtered(?string $term, array $criteria): Builder
    {
        $term = trim((string) $term);
        $state = $criteria[self::FILTER_STATE] ?? null;
        $channels = array_values(array_map('intval', array_filter((array) ($criteria[self::FILTER_CHANNEL] ?? []), 'is_numeric')));
        $message = $criteria[self::FILTER_MESSAGE] ?? null;

        return $this->query()
            ->when($term !== '', function (Builder $query) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(function (Builder $query) use ($like): void {
                    $query->where('reason', 'like', $like)
                        ->orWhereHas('message', fn (Builder $message) => $message->withTrashed()->where('body', 'like', $like))
                        ->orWhereHas('reporter', fn (Builder $reporter) => $reporter->where('name', 'like', $like));
                });
            })
            ->when($state === self::STATE_OPEN, fn (Builder $query) => $query->open())
            ->when($state === self::STATE_RESOLVED, fn (Builder $query) => $query->whereNotNull('resolved_at'))
            ->when($channels !== [], fn (Builder $query) => $query->whereHas('message', fn (Builder $message) => $message->withTrashed()->whereIn('channel_id', $channels)))
            ->when($message === self::MESSAGE_REMOVED, fn (Builder $query) => $query->where(fn (Builder $query) => $query->doesntHave('message')->orWhereHas('message', fn (Builder $message) => $message->onlyTrashed())))
            ->when($message === self::MESSAGE_VISIBLE, fn (Builder $query) => $query->whereHas('message'));
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
