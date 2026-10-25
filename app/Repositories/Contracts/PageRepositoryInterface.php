<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Constants\PageBlockType;
use App\Models\Page;
use App\Models\PageBlock;
use Illuminate\Database\Eloquent\Collection;

/**
 * The editable pages (App\Constants\Pages) and their blocks.
 */
interface PageRepositoryInterface
{
    /**
     * Every stored page in admin order.
     *
     * @return Collection<int, Page>
     */
    public function all(): Collection;

    public function find(int $id): ?Page;

    public function findOrFail(int $id): Page;

    public function findByKey(string $key): ?Page;

    /**
     * The page with this key, created with the given attributes when it does not exist yet.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function firstOrCreate(string $key, array $attributes = []): Page;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Page $page, array $attributes): Page;

    /**
     * The page's blocks keyed by block key, with their media loaded.
     *
     * @return Collection<string, PageBlock>
     */
    public function blocks(Page $page): Collection;

    public function block(Page $page, string $key): ?PageBlock;

    /**
     * Create the block or update the given attributes of it.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function saveBlock(Page $page, string $key, PageBlockType $type, array $attributes): PageBlock;

    public function deleteBlock(Page $page, string $key): bool;

    /**
     * How many of the page's blocks hold an unpublished change.
     */
    public function countDrafts(Page $page): int;
}
