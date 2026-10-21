<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Video;
use App\Repositories\Contracts\VideoRepositoryInterface;

/**
 * @extends BaseContentRepository<Video>
 */
class EloquentVideoRepository extends BaseContentRepository implements VideoRepositoryInterface
{
    protected array $searchColumns = ['title', 'event', 'location', 'description'];

    protected array $sortableColumns = ['title', 'date', 'provider'];

    protected array $filterColumns = ['status', 'provider', 'is_featured'];

    protected array $publishedWith = ['media'];

    public function __construct(Video $video)
    {
        parent::__construct($video);
    }

    public function findBySlug(string $slug): ?Video
    {
        return $this->query()->with($this->publishedWith)->where('slug', $slug)->first();
    }

    public function findPublishedBySlug(string $slug): ?Video
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
