<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\ChatReport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<ChatReport>
 */
interface ChatReportRepositoryInterface extends BaseRepositoryInterface
{
    public function findFor(int $messageId, int $reporterId): ?ChatReport;

    /**
     * A report with its (possibly deleted) message, the message's author and the reporter.
     */
    public function findWithRelations(int $id): ?ChatReport;

    /**
     * Open reports, oldest first, with their (possibly deleted) messages.
     *
     * @return Collection<int, ChatReport>
     */
    public function open(): Collection;

    public function countOpen(): int;

    /**
     * Open or resolved reports, a page at a time, with their relations.
     *
     * @return LengthAwarePaginator<int, ChatReport>
     */
    public function paginateByState(bool $open, int $perPage = 20): LengthAwarePaginator;

    public function markResolved(ChatReport $report, int $resolvedBy): ChatReport;

    /**
     * Resolve every open report about a message. Returns how many were resolved.
     */
    public function markResolvedForMessage(int $messageId, int $resolvedBy): int;
}
