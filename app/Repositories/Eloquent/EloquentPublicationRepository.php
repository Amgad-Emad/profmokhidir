<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Constants\PublicationType;
use App\Models\Publication;
use App\Repositories\Contracts\PublicationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseContentRepository<Publication>
 */
class EloquentPublicationRepository extends BaseContentRepository implements PublicationRepositoryInterface
{
    protected array $searchColumns = ['title', 'authors', 'venue_publisher', 'abstract', 'doi'];

    protected array $filterColumns = ['status', 'type', 'year'];

    protected array $publishedWith = ['media'];

    public function __construct(Publication $publication)
    {
        parent::__construct($publication);
    }

    public function findBySlug(string $slug): ?Publication
    {
        return $this->query()->where('slug', $slug)->first();
    }

    public function findPublishedBySlug(string $slug): ?Publication
    {
        return $this->publishedQuery()->where('slug', $slug)->first();
    }

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return $this->query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    public function paginatePublishedFiltered(?PublicationType $type = null, ?int $year = null, int $perPage = 20): LengthAwarePaginator
    {
        return $this->publishedQuery()
            ->when($type !== null, fn ($query) => $query->ofType($type))
            ->when($year !== null, fn ($query) => $query->inYear($year))
            ->paginate($perPage)
            ->withQueryString();
    }

    public function latestPublished(int $limit = 3): Collection
    {
        return $this->query()->published()->with('media')
            ->orderByDesc('year')->orderBy('sort_order')->orderByDesc('id')
            ->limit($limit)->get();
    }

    public function publishedYears(): array
    {
        return $this->query()->published()->whereNotNull('year')
            ->distinct()->orderByDesc('year')->pluck('year')
            ->map(fn ($year): int => (int) $year)->all();
    }

    public function publishedTypes(): array
    {
        $types = $this->query()->published()->distinct()->pluck('type')
            ->map(fn ($type): string => $type instanceof PublicationType ? $type->value : (string) $type)
            ->all();

        return array_values(array_filter(
            PublicationType::cases(),
            fn (PublicationType $type): bool => in_array($type->value, $types, true),
        ));
    }
}
