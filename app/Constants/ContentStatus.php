<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

/**
 * Publication status shared by every public content type.
 * Models expose it through a `status` column and a `published()` scope.
 */
enum ContentStatus: string
{
    use HasValues;

    case Draft = 'draft';
    case Published = 'published';

    public static function default(): self
    {
        return self::Draft;
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
        };
    }
}
