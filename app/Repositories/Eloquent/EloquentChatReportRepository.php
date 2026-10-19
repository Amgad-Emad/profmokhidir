<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\ChatReport;
use App\Repositories\Contracts\ChatReportRepositoryInterface;
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

    public function open(): Collection
    {
        return $this->query()->open()->with(['message.user', 'reporter'])->orderBy('id')->get();
    }

    public function countOpen(): int
    {
        return $this->query()->open()->count();
    }

    public function markResolved(ChatReport $report, int $resolvedBy): ChatReport
    {
        $report->forceFill(['resolved_by' => $resolvedBy, 'resolved_at' => now()])->save();

        return $report->refresh();
    }
}
