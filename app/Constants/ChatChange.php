<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

/**
 * What happened to a chat message, carried by the ChatMessageChanged event.
 */
enum ChatChange: string
{
    use HasValues;

    case Sent = 'sent';
    case Edited = 'edited';
    case Deleted = 'deleted';

    public function label(): string
    {
        return match ($this) {
            self::Sent => 'sent',
            self::Edited => 'edited',
            self::Deleted => 'deleted',
        };
    }
}
