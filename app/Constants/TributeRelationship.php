<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

enum TributeRelationship: string
{
    use HasValues;

    case Student = 'student';
    case Colleague = 'colleague';
    case Family = 'family';
    case Friend = 'friend';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Student',
            self::Colleague => 'Colleague',
            self::Family => 'Family',
            self::Friend => 'Friend',
            self::Other => 'Other',
        };
    }
}
