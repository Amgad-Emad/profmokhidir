<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class TributeLimitReachedException extends RuntimeException
{
    public static function make(): self
    {
        return new self(__('Too many tributes have been submitted from this address recently. Please try again later.'));
    }
}
