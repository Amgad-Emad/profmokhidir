<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Constants\RevisionKind;
use App\Models\Revision;
use App\Repositories\Contracts\RevisionRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends BaseEloquentRepository<Revision>
 */
class EloquentRevisionRepository extends BaseEloquentRepository implements RevisionRepositoryInterface
{
    public function __construct(Revision $revision)
    {
        parent::__construct($revision);
    }

    public function record(Model $record, ?int $userId, RevisionKind $kind, array $data): Revision
    {
        /** @var Revision */
        return $this->query()->create([
            'revisionable_type' => $record->getMorphClass(),
            'revisionable_id' => $record->getKey(),
            'user_id' => $userId,
            'kind' => $kind,
            'data' => $data,
            'created_at' => now(),
        ]);
    }

    public function forRecord(Model $record, int $limit = 50): Collection
    {
        return $this->ofRecord($record)->with('user')->latest('created_at')->latest('id')->limit($limit)->get();
    }

    public function latestFor(Model $record): ?Revision
    {
        return $this->ofRecord($record)->latest('created_at')->latest('id')->first();
    }

    public function findForRecord(Model $record, int $id): ?Revision
    {
        return $this->ofRecord($record)->whereKey($id)->first();
    }

    public function countFor(Model $record): int
    {
        return $this->ofRecord($record)->count();
    }

    public function prune(Model $record, int $keep): int
    {
        $keepIds = $this->ofRecord($record)->latest('created_at')->latest('id')->limit($keep)->pluck('id');

        return $this->ofRecord($record)->whereNotIn('id', $keepIds)->delete();
    }

    public function forget(Model $record): int
    {
        return $this->ofRecord($record)->delete();
    }

    /**
     * @return Builder<Revision>
     */
    private function ofRecord(Model $record): Builder
    {
        return $this->query()
            ->where('revisionable_type', $record->getMorphClass())
            ->where('revisionable_id', $record->getKey());
    }
}
