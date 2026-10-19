<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\OtherItem;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends ContentRepositoryInterface<OtherItem>
 */
interface OtherItemRepositoryInterface extends ContentRepositoryInterface
{
    public function findBySlug(string $slug): ?OtherItem;

    public function findPublishedBySlug(string $slug): ?OtherItem;

    public function slugExists(string $slug, ?int $ignoreId = null): bool;

    /**
     * @return Collection<int, OtherItem>
     */
    public function publishedInCategory(string $category): Collection;

    /**
     * Distinct categories across published items, alphabetical.
     *
     * @return list<string>
     */
    public function publishedCategories(): array;
}
