<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TeachingMaterial;
use App\Models\User;
use App\Services\TeachingMaterialService;

class TeachingMaterialPolicy
{
    public function __construct(private readonly TeachingMaterialService $materials) {}

    /**
     * Download the attached file. Guests may download public files of published
     * materials; members-only files need an approved member or staff.
     */
    public function download(?User $user, TeachingMaterial $material): bool
    {
        return $this->materials->canDownload($material, $user);
    }
}
