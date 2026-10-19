<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CvEntry;

/**
 * @see ContentPolicy for the rules; every ability needs "manage content".
 */
class CvEntryPolicy extends ContentPolicy
{
    /** The model this policy guards. */
    public const string MODEL = CvEntry::class;
}
