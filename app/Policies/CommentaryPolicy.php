<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Commentary;

/**
 * @see ContentPolicy for the rules; every ability needs "manage content".
 */
class CommentaryPolicy extends ContentPolicy
{
    /** The model this policy guards. */
    public const string MODEL = Commentary::class;
}
