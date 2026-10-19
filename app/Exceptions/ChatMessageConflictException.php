<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an edit carries a stale version (optimistic locking).
 */
class ChatMessageConflictException extends RuntimeException
{
    public static function forVersion(int $expected): self
    {
        return new self(sprintf('The message was changed by someone else (expected version %d).', $expected));
    }
}
