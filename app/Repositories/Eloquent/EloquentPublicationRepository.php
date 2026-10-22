<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Constants\Catalogue;
use App\Constants\PublicationType;
use App\Models\Publication;
use App\Repositories\Contracts\PublicationRepositoryInterface;
use App\Support\PublicationFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseContentRepository<Publication>
 */
class EloquentPublicationRepository extends BaseContentRepository implements PublicationRepositoryInterface
{
    protected array $searchColumns = ['title', 'authors', 'venue_publisher', 'abstract', 'doi', 'topic'];

    protected array $placeholderColumns = ['citation'];

    protected array $sortableColumns = ['title', 'type', 'year'];

    protected array $filterColumns = ['status', 'type', 'year', 'is_featured', 'topic'];

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

    public function paginatePublishedFiltered(PublicationFilters $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->catalogueQuery($filters)
            ->with($this->publishedWith)
            ->orderByRaw('year is null')
            ->orderByDesc('year')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function publishedDecadeCounts(PublicationFilters $filters): array
    {
        $rows = $this->catalogueQuery($filters)
            ->selectRaw('floor(year / ?) * ? as decade, count(*) as total', [Catalogue::DECADE, Catalogue::DECADE])
            ->groupBy('decade')
            ->get();

        $counts = [];

        foreach ($rows->sortByDesc(fn ($row): int => $row->decade === null ? PHP_INT_MIN : (int) $row->decade) as $row) {
            $counts[$row->decade === null ? '' : (int) $row->decade] = (int) $row->total;
        }

        return $counts;
    }

    public function publishedFeatured(int $limit): Collection
    {
        return $this->publishedQuery()->featured()->limit($limit)->get();
    }

    public function publishedTopics(): array
    {
        return $this->query()->published()->whereNotNull('topic')->where('topic', '!=', '')
            ->distinct()->orderBy('topic')->pluck('topic')
            ->map(fn ($topic): string => (string) $topic)->values()->all();
    }

    /**
     * Published records matching the filters, unordered.
     *
     * @return Builder<Publication>
     */
    private function catalogueQuery(PublicationFilters $filters): Builder
    {
        return $this->query()->published()
            ->when($filters->type !== null, fn (Builder $query) => $query->ofType($filters->type))
            ->when($filters->from !== null || $filters->to !== null, fn (Builder $query) => $query->betweenYears($filters->from, $filters->to))
            ->when($filters->topic !== null, fn (Builder $query) => $query->onTopic((string) $filters->topic));
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
