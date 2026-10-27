<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Constants\CommunityStatus;
use RuntimeException;

class CommunityApplicationException extends RuntimeException
{
    public static function invalidTransition(CommunityStatus $from, CommunityStatus $to): self
    {
        return new self(__('A :from application cannot become :to.', ['from' => $from->value, 'to' => $to->value]));
    }
}
