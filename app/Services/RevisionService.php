<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AdminUx;
use App\Constants\RevisionKind;
use App\Models\Revision;
use App\Models\User;
use App\Repositories\Contracts\RevisionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Version history of the edit screens. Every save, publish and autosave
 * stores the form values; an earlier version can be loaded back into the form
 * (the editor then saves it as usual). Identical consecutive versions are not
 * stored twice, and only the newest AdminUx::REVISIONS_KEPT are kept.
 */
class RevisionService
{
    public function __construct(private readonly RevisionRepositoryInterface $revisions) {}

    /**
     * Store the form values of a record, unless they match the latest version.
     *
     * @param  array<string, mixed>  $data
     */
    public function record(Model $record, array $data, RevisionKind $kind, ?User $actor = null): ?Revision
    {
        $latest = $this->revisions->latestFor($record);

        if ($latest !== null && $latest->data == $data && $kind !== RevisionKind::Publish) {
            return null;
        }

        $revision = $this->revisions->record($record, $actor?->getKey(), $kind, $data);
        $this->revisions->prune($record, AdminUx::REVISIONS_KEPT);

        return $revision;
    }

    /**
     * @return Collection<int, Revision>
     */
    public function history(Model $record, int $limit = AdminUx::REVISIONS_SHOWN): Collection
    {
        return $this->revisions->forRecord($record, $limit);
    }

    public function count(Model $record): int
    {
        return $this->revisions->countFor($record);
    }

    /**
     * Form values of one revision of the record, or null when the revision
     * does not belong to it.
     *
     * @return array<string, mixed>|null
     */
    public function dataFor(Model $record, int $revisionId): ?array
    {
        $revision = $this->revisions->findForRecord($record, $revisionId);

        return $revision === null ? null : (array) $revision->data;
    }
}
