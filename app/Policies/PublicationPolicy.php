<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Publication;

/**
 * @see ContentPolicy for the rules; every ability needs "manage content".
 */
class PublicationPolicy extends ContentPolicy
{
    /** The model this policy guards. */
    public const string MODEL = Publication::class;
}
