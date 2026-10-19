<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

/**
 * Who may download a teaching material. Members-only files live on the private disk.
 */
enum TeachingAccess: string
{
    use HasValues;

    case Public = 'public';
    case Members = 'members';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Public',
            self::Members => 'Community members',
        };
    }

    public function disk(): string
    {
        return $this === self::Members ? Disks::PRIVATE : Disks::PUBLIC;
    }
}
