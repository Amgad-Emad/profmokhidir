<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Constants\RevisionKind;
use App\Models\Revision;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends BaseRepositoryInterface<Revision>
 */
interface RevisionRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function record(Model $record, ?int $userId, RevisionKind $kind, array $data): Revision;

    /**
     * Revisions of one record, newest first.
     *
     * @return Collection<int, Revision>
     */
    public function forRecord(Model $record, int $limit = 50): Collection;

    public function latestFor(Model $record): ?Revision;

    /**
     * A revision only when it belongs to the given record.
     */
    public function findForRecord(Model $record, int $id): ?Revision;

    public function countFor(Model $record): int;

    /**
     * Delete all but the newest $keep revisions of a record; returns how many went.
     */
    public function prune(Model $record, int $keep): int;

    /**
     * Delete every revision of a record (when the record itself is deleted for good).
     */
    public function forget(Model $record): int;
}
