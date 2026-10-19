<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Constants\CommunityStatus;
use RuntimeException;

class CommunityApplicationException extends RuntimeException
{
    public static function invalidTransition(CommunityStatus $from, CommunityStatus $to): self
    {
        return new self(sprintf('A %s application cannot become %s.', $from->value, $to->value));
    }
}
