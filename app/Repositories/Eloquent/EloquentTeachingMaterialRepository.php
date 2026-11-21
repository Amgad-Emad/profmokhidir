<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Constants\TeachingAccess;
use App\Models\TeachingMaterial;
use App\Repositories\Contracts\TeachingMaterialRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseContentRepository<TeachingMaterial>
 */
class EloquentTeachingMaterialRepository extends BaseContentRepository implements TeachingMaterialRepositoryInterface
{
    protected array $searchColumns = ['title', 'course', 'topic', 'description'];

    protected array $placeholderColumns = ['course_description'];

    protected array $sortableColumns = ['title', 'course', 'type', 'material_date', 'access'];

    protected array $filterColumns = ['status', 'type', 'access', 'course', 'topic'];

    protected array $publishedWith = ['media'];

    public function __construct(TeachingMaterial $material)
    {
        parent::__construct($material);
    }

    public function publishedWithAccess(TeachingAccess ...$access): Collection
    {
        return $this->publishedQuery()->withAccess(...$access)->get();
    }
}
