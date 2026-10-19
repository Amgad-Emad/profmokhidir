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

    /** How long a signed link to a members-only file stays valid. */
    public const int SIGNED_URL_MINUTES = 60;

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
