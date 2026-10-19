<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Award;

/**
 * @see ContentPolicy for the rules; every ability needs "manage content".
 */
class AwardPolicy extends ContentPolicy
{
    /** The model this policy guards. */
    public const string MODEL = Award::class;
}
