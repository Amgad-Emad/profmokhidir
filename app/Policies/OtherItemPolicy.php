<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\OtherItem;

/**
 * @see ContentPolicy for the rules; every ability needs "manage content".
 */
class OtherItemPolicy extends ContentPolicy
{
    /** The model this policy guards. */
    public const string MODEL = OtherItem::class;
}
