<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Commentary;
use App\Repositories\Contracts\CommentaryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends BaseContentRepository<Commentary>
 */
class EloquentCommentaryRepository extends BaseContentRepository implements CommentaryRepositoryInterface
{
    protected array $searchColumns = ['title', 'summary', 'byline'];

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

    public function publishedTags(): array
    {
        $tags = $this->query()->published()->whereNotNull('tags')->pluck('tags')
            ->flatten()->filter()->map(fn ($tag): string => (string) $tag)->unique()->sort()->values()->all();

        return $tags;
    }
}
