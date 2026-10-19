<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Constants\TeachingAccess;
use App\Models\TeachingMaterial;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends ContentRepositoryInterface<TeachingMaterial>
 */
interface TeachingMaterialRepositoryInterface extends ContentRepositoryInterface
{
    /**
     * Published materials limited to the given access levels.
     *
     * @return Collection<int, TeachingMaterial>
     */
    public function publishedWithAccess(TeachingAccess ...$access): Collection;
}
