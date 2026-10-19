<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Constants\PublicationType;
use App\Models\Publication;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends ContentRepositoryInterface<Publication>
 */
interface PublicationRepositoryInterface extends ContentRepositoryInterface
{
    public function findBySlug(string $slug): ?Publication;

    public function findPublishedBySlug(string $slug): ?Publication;

    public function slugExists(string $slug, ?int $ignoreId = null): bool;

    /**
     * @return LengthAwarePaginator<int, Publication>
     */
    public function paginatePublishedFiltered(?PublicationType $type = null, ?int $year = null, int $perPage = 20): LengthAwarePaginator;

    /**
     * @return Collection<int, Publication>
     */
    public function latestPublished(int $limit = 3): Collection;

    /**
     * Distinct years among published publications, newest first.
     *
     * @return list<int>
     */
    public function publishedYears(): array;

    /**
     * Distinct types among published publications.
     *
     * @return list<PublicationType>
     */
    public function publishedTypes(): array;
}
