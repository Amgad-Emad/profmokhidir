<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

/**
 * What a chat attachment is: an image (shown as a thumbnail that opens in the
 * lightbox, needs a short description for its alt text) or a document (shown
 * as a card with a Download link).
 */
enum ChatAttachmentKind: string
{
    use HasValues;

    case Image = 'image';
    case Document = 'document';

    public function label(): string
    {
        return match ($this) {
            self::Image => __('Image'),
            self::Document => __('Document'),
        };
    }
}
