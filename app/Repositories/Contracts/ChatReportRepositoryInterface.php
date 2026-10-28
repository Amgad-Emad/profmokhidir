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
    public const string FILTER_STATE = 'state';

    public const string FILTER_CHANNEL = 'channel';

    public const string FILTER_MESSAGE = 'message';

    public const string STATE_OPEN = 'open';

    public const string STATE_RESOLVED = 'resolved';

    public const string MESSAGE_VISIBLE = 'visible';

    public const string MESSAGE_REMOVED = 'removed';

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

    /**
     * Reports for the moderation list: a search over the reason, the message
     * and the reporter's name, then FILTER_* criteria (state, channel ids,
     * message visible or removed), newest first unless `oldestFirst`.
     *
     * @param  array<string, string|list<string>>  $criteria
     * @return LengthAwarePaginator<int, ChatReport>
     */
    public function filter(?string $term, array $criteria, int $perPage = 20, bool $oldestFirst = false): LengthAwarePaginator;

    /**
     * @param  array<string, string|list<string>>  $criteria
     */
    public function countFiltered(?string $term, array $criteria): int;

    public function markResolved(ChatReport $report, int $resolvedBy): ChatReport;

    /**
     * Resolve every open report about a message. Returns how many were resolved.
     */
    public function markResolvedForMessage(int $messageId, int $resolvedBy): int;
}
