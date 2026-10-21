<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Constants\PublicationType;
use App\Models\Publication;
use App\Support\PublicationFilters;
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
     * The public catalogue: published records matching the filters, newest year
     * first (undated last), then display order, so decade groups never split.
     *
     * @return LengthAwarePaginator<int, Publication>
     */
    public function paginatePublishedFiltered(PublicationFilters $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * How many published records match the filters in each decade (1970 => 4),
     * newest first; undated records are counted under the key "".
     *
     * @return array<int|string, int>
     */
    public function publishedDecadeCounts(PublicationFilters $filters): array;

    /**
     * Published records an editor marked as featured, in display order.
     *
     * @return Collection<int, Publication>
     */
    public function publishedFeatured(int $limit): Collection;

    /**
     * Distinct topics among published publications, alphabetical.
     *
     * @return list<string>
     */
    public function publishedTopics(): array;

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
