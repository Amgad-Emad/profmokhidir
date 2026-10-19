<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an edit carries a stale version (optimistic locking).
 */
class ChatMessageConflictException extends RuntimeException
{
    public const string MESSAGE = 'This message was changed elsewhere, reload it.';

    public static function forVersion(int $expected): self
    {
        $exception = new self(self::MESSAGE);
        $exception->expectedVersion = $expected;

        return $exception;
    }

    public int $expectedVersion = 0;
}
