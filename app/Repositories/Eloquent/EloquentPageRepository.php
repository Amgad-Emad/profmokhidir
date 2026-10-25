<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Constants\PageBlockType;
use App\Models\Page;
use App\Models\PageBlock;
use App\Repositories\Contracts\PageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentPageRepository implements PageRepositoryInterface
{
    public function __construct(
        private readonly Page $pages,
        private readonly PageBlock $blocks,
    ) {}

    public function all(): Collection
    {
        return $this->pages->newQuery()->orderBy('sort_order')->orderBy('id')->get();
    }

    public function find(int $id): ?Page
    {
        return $this->pages->newQuery()->find($id);
    }

    public function findOrFail(int $id): Page
    {
        return $this->pages->newQuery()->findOrFail($id);
    }

    public function findByKey(string $key): ?Page
    {
        return $this->pages->newQuery()->where('key', $key)->first();
    }

    public function firstOrCreate(string $key, array $attributes = []): Page
    {
        return $this->pages->newQuery()->firstOrCreate(['key' => $key], $attributes);
    }

    public function update(Page $page, array $attributes): Page
    {
        $page->fill($attributes)->save();

        return $page->refresh();
    }

    public function blocks(Page $page): Collection
    {
        return $this->blocks->newQuery()
            ->where('page_id', $page->getKey())
            ->with('media')
            ->orderBy('sort_order')
            ->get()
            ->keyBy('key');
    }

    public function block(Page $page, string $key): ?PageBlock
    {
        return $this->blocks->newQuery()->where('page_id', $page->getKey())->where('key', $key)->first();
    }

    public function saveBlock(Page $page, string $key, PageBlockType $type, array $attributes): PageBlock
    {
        return $this->blocks->newQuery()->updateOrCreate(
            ['page_id' => $page->getKey(), 'key' => $key],
            ['type' => $type] + $attributes,
        );
    }

    public function deleteBlock(Page $page, string $key): bool
    {
        return $this->blocks->newQuery()->where('page_id', $page->getKey())->where('key', $key)->delete() > 0;
    }

    public function countDrafts(Page $page): int
    {
        return $this->blocks->newQuery()->where('page_id', $page->getKey())->whereNotNull('draft_value')->count();
    }
}
