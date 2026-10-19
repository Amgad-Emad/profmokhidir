<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Album;

/**
 * @extends ContentRepositoryInterface<Album>
 */
interface AlbumRepositoryInterface extends ContentRepositoryInterface
{
    public function findBySlug(string $slug): ?Album;

    public function findPublishedBySlug(string $slug): ?Album;

    public function slugExists(string $slug, ?int $ignoreId = null): bool;
}
