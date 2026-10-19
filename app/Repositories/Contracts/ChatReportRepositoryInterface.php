<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\ChatReport;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<ChatReport>
 */
interface ChatReportRepositoryInterface extends BaseRepositoryInterface
{
    public function findFor(int $messageId, int $reporterId): ?ChatReport;

    /**
     * @return Collection<int, ChatReport>
     */
    public function open(): Collection;

    public function countOpen(): int;

    public function markResolved(ChatReport $report, int $resolvedBy): ChatReport;
}
