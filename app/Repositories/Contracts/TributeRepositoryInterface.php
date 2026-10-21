<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Constants\TributeRelationship;
use App\Constants\TributeStatus;
use App\Models\Tribute;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends ContentRepositoryInterface<Tribute>
 */
interface TributeRepositoryInterface extends ContentRepositoryInterface
{
    /**
     * @return Collection<int, Tribute>
     */
    public function withStatus(TributeStatus $status): Collection;

    /**
     * @return LengthAwarePaginator<int, Tribute>
     */
    public function paginateByStatus(?TributeStatus $status = null, int $perPage = 20): LengthAwarePaginator;

    public function countByStatus(TributeStatus $status): int;

    /**
     * @return Collection<int, Tribute>
     */
    public function latestApproved(int $limit = 3): Collection;

    /**
     * Number of tributes submitted from the given hashed IP since the given time (rate limiting).
     */
    public function countRecentFromIp(string $ipHash, \DateTimeInterface $since): int;

    /**
     * The pages of the book: approved tributes in display order, optionally one
     * relationship only, leaving out the featured tribute (it opens the book).
     *
     * @return LengthAwarePaginator<int, Tribute>
     */
    public function paginateApproved(?TributeRelationship $relationship, int $perPage, ?int $exceptId = null): LengthAwarePaginator;

    /**
     * The approved tribute editors chose as the frontispiece, if any.
     */
    public function featured(): ?Tribute;

    /**
     * Clear the featured flag everywhere (except, optionally, one tribute); returns how many changed.
     */
    public function clearFeatured(?int $exceptId = null): int;

    /**
     * Approved tributes whose share card has not been drawn yet.
     *
     * @return Collection<int, Tribute>
     */
    public function approvedWithoutShareCard(): Collection;
}
