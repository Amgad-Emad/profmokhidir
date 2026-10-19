<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Commentary;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends ContentRepositoryInterface<Commentary>
 */
interface CommentaryRepositoryInterface extends ContentRepositoryInterface
{
    public function findBySlug(string $slug): ?Commentary;

    public function findPublishedBySlug(string $slug): ?Commentary;

    public function slugExists(string $slug, ?int $ignoreId = null): bool;

    /**
     * @return LengthAwarePaginator<int, Commentary>
     */
    public function paginatePublishedTagged(?string $tag = null, int $perPage = 10): LengthAwarePaginator;

    /**
     * The published commentaries either side of the given one in listing order:
     * `previous` is the one listed before it (newer), `next` the one after (older).
     *
     * @return array{previous: Commentary|null, next: Commentary|null}
     */
    public function neighbours(Commentary $commentary): array;

    /**
     * Distinct tags across published commentaries, alphabetical.
     *
     * @return list<string>
     */
    public function publishedTags(): array;
}
