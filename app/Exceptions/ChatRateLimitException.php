<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a member sends more messages than ChatSettings allows per minute.
 */
class ChatRateLimitException extends RuntimeException
{
    public static function make(int $retryAfterSeconds): self
    {
        $exception = new self(__('You are sending messages too quickly. Please wait :seconds seconds.', ['seconds' => max(1, $retryAfterSeconds)]));
        $exception->retryAfter = max(1, $retryAfterSeconds);

        return $exception;
    }

    public int $retryAfter = 1;
}
