<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Album;

/**
 * @see ContentPolicy for the rules; every ability needs "manage content".
 */
class AlbumPolicy extends ContentPolicy
{
    /** The model this policy guards. */
    public const string MODEL = Album::class;
}
