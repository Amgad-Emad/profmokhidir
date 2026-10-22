<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Commentary;
use App\Repositories\Contracts\CommentaryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseContentRepository<Commentary>
 */
class EloquentCommentaryRepository extends BaseContentRepository implements CommentaryRepositoryInterface
{
    protected array $searchColumns = ['title', 'summary', 'byline'];

    protected array $placeholderColumns = ['body', 'epigraph', 'epigraph_attribution'];

    protected array $sortableColumns = ['title', 'date'];

    public function __construct(Commentary $commentary)
    {
        parent::__construct($commentary);
    }

    public function findBySlug(string $slug): ?Commentary
    {
        return $this->query()->with($this->publishedWith)->where('slug', $slug)->first();
    }

    public function findPublishedBySlug(string $slug): ?Commentary
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

    public function paginatePublishedTagged(?string $tag = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->publishedQuery()
            ->when($tag !== null && $tag !== '', fn ($query) => $query->tagged($tag))
            ->paginate($perPage)
            ->withQueryString();
    }

    public function neighbours(Commentary $commentary): array
    {
        $ids = $this->publishedQuery()->pluck('id')->map(fn ($id): int => (int) $id)->values();
        $position = $ids->search((int) $commentary->getKey());

        if ($position === false) {
            return ['previous' => null, 'next' => null];
        }

        $previousId = $position > 0 ? $ids->get($position - 1) : null;
        $nextId = $ids->get($position + 1);

        return [
            'previous' => $previousId === null ? null : $this->publishedQuery()->find($previousId),
            'next' => $nextId === null ? null : $this->publishedQuery()->find($nextId),
        ];
    }

    public function publishedRelated(Commentary $commentary, int $limit): Collection
    {
        $tags = array_values(array_filter((array) $commentary->tags, fn ($tag): bool => is_string($tag) && trim($tag) !== ''));

        if ($tags === [] || $limit < 1) {
            return new Collection;
        }

        // Tags match without regard to case, as the tag filter does; JSON_CONTAINS would not, so the match is made here.
        $shared = fn (Commentary $candidate): int => count(array_uintersect(
            array_filter((array) $candidate->tags, 'is_string'),
            $tags,
            fn (string $a, string $b): int => strcmp(mb_strtolower(trim($a)), mb_strtolower(trim($b))),
        ));

        return $this->query()->published()
            ->whereKeyNot($commentary->getKey())
            ->whereNotNull('tags')
            ->get()
            ->filter(fn (Commentary $candidate): bool => $shared($candidate) > 0)
            ->sort(fn (Commentary $a, Commentary $b): int => [$shared($b), $b->date?->getTimestamp() ?? 0, $b->getKey()] <=> [$shared($a), $a->date?->getTimestamp() ?? 0, $a->getKey()])
            ->take($limit)
            ->values();
    }

    public function publishedTags(): array
    {
        $tags = $this->query()->published()->whereNotNull('tags')->pluck('tags')
            ->flatten()->filter()->map(fn ($tag): string => (string) $tag)->unique()->sort()->values()->all();

        return $tags;
    }
}
