<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

/**
 * Brand photo treatment applied to an image on the public site.
 */
enum MediaTreatment: string
{
    use HasValues;

    case Portrait = 'portrait';
    case Archive = 'archive';

    public function label(): string
    {
        return match ($this) {
            self::Portrait => __('Portrait (warm monochrome)'),
            self::Archive => __('Archive (evergreen duotone)'),
        };
    }
}
