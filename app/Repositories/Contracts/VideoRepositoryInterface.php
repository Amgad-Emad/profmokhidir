<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Video;

/**
 * @extends ContentRepositoryInterface<Video>
 */
interface VideoRepositoryInterface extends ContentRepositoryInterface
{
    public function findBySlug(string $slug): ?Video;

    public function findPublishedBySlug(string $slug): ?Video;

    public function slugExists(string $slug, ?int $ignoreId = null): bool;
}
