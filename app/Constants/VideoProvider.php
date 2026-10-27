<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

enum VideoProvider: string
{
    use HasValues;

    case YouTube = 'youtube';
    case Vimeo = 'vimeo';
    case File = 'file';
    case Link = 'link';

    public function label(): string
    {
        return match ($this) {
            self::YouTube => 'YouTube',
            self::Vimeo => 'Vimeo',
            self::File => __('Uploaded file'),
            self::Link => __('External link'),
        };
    }

    /**
     * Providers that embed a player rather than linking out.
     */
    public function isEmbeddable(): bool
    {
        return $this !== self::Link;
    }
}
