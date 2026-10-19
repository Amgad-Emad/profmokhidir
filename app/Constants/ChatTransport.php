<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

/**
 * How chat clients learn about new messages. Polling needs nothing beyond
 * Livewire; Broadcast expects Laravel Reverb (or another broadcaster) and
 * Echo on the page. Either way clients fetch from their cursor, so switching
 * changes only how quickly they are nudged.
 */
enum ChatTransport: string
{
    use HasValues;

    case Poll = 'poll';
    case Broadcast = 'broadcast';

    public static function default(): self
    {
        return self::Poll;
    }

    public function label(): string
    {
        return match ($this) {
            self::Poll => 'Polling',
            self::Broadcast => 'Broadcasting (Reverb)',
        };
    }

    public function broadcasts(): bool
    {
        return $this === self::Broadcast;
    }
}
