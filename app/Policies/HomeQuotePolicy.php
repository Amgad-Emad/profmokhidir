<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\HomeQuote;

/**
 * @see ContentPolicy for the rules; every ability needs "manage content".
 */
class HomeQuotePolicy extends ContentPolicy
{
    /** The model this policy guards. */
    public const string MODEL = HomeQuote::class;
}
