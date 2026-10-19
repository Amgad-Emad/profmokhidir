<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Video;

/**
 * @see ContentPolicy for the rules; every ability needs "manage content".
 */
class VideoPolicy extends ContentPolicy
{
    /** The model this policy guards. */
    public const string MODEL = Video::class;
}
