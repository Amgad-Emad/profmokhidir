<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Profile;

/**
 * @see ContentPolicy for the rules; every ability needs "manage content".
 */
class ProfilePolicy extends ContentPolicy
{
    /** The model this policy guards. */
    public const string MODEL = Profile::class;
}
