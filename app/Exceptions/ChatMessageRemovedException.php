<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when someone edits or reports a message that has already been deleted.
 */
class ChatMessageRemovedException extends RuntimeException
{
    public static function make(): self
    {
        return new self(__('This message has been removed.'));
    }
}
