<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TeachingMaterial;
use App\Models\User;
use App\Services\TeachingMaterialService;

/**
 * Editing follows ContentPolicy ("manage content"). Downloading is public for
 * public files of published materials; members-only files need an approved
 * member or staff.
 */
class TeachingMaterialPolicy extends ContentPolicy
{
    /** The model this policy guards. */
    public const string MODEL = TeachingMaterial::class;

    public function __construct(private readonly TeachingMaterialService $materials) {}

    public function download(?User $user, TeachingMaterial $material): bool
    {
        return $this->materials->canDownload($material, $user);
    }
}
