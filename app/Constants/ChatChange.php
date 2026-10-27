<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

/**
 * What happened to a chat message, carried by the ChatMessageChanged event:
 * sent, edited, deleted, or pinned and unpinned by a moderator.
 */
enum ChatChange: string
{
    use HasValues;

    case Sent = 'sent';
    case Edited = 'edited';
    case Deleted = 'deleted';
    case Pinned = 'pinned';
    case Unpinned = 'unpinned';

    public function label(): string
    {
        return match ($this) {
            self::Sent => __('sent'),
            self::Edited => __('edited'),
            self::Deleted => __('deleted'),
            self::Pinned => __('pinned'),
            self::Unpinned => __('unpinned'),
        };
    }
}
