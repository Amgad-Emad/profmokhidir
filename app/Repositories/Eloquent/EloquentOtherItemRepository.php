<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\OtherItem;
use App\Repositories\Contracts\OtherItemRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseContentRepository<OtherItem>
 */
class EloquentOtherItemRepository extends BaseContentRepository implements OtherItemRepositoryInterface
{
    protected array $searchColumns = ['title', 'summary', 'category'];

    protected array $sortableColumns = ['title', 'date', 'category'];

    protected array $filterColumns = ['status', 'item_type', 'category'];

    protected array $publishedWith = ['media'];

    public function __construct(OtherItem $item)
    {
        parent::__construct($item);
    }

    public function findBySlug(string $slug): ?OtherItem
    {
        return $this->query()->with($this->publishedWith)->where('slug', $slug)->first();
    }

    public function findPublishedBySlug(string $slug): ?OtherItem
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

    public function publishedInCategory(string $category): Collection
    {
        return $this->publishedQuery()->inCategory($category)->get();
    }

    public function publishedCategories(): array
    {
        return $this->query()->published()->whereNotNull('category')
            ->distinct()->orderBy('category')->pluck('category')->all();
    }
}
