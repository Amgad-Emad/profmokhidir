<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Album;
use App\Repositories\Contracts\AlbumRepositoryInterface;

/**
 * @extends BaseContentRepository<Album>
 */
class EloquentAlbumRepository extends BaseContentRepository implements AlbumRepositoryInterface
{
    protected array $searchColumns = ['title', 'description'];

    protected array $publishedWith = ['media'];

    public function __construct(Album $album)
    {
        parent::__construct($album);
    }

    public function findBySlug(string $slug): ?Album
    {
        return $this->query()->with($this->publishedWith)->where('slug', $slug)->first();
    }

    public function findPublishedBySlug(string $slug): ?Album
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
}
