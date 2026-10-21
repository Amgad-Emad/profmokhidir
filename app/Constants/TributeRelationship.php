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

    /**
     * The relationships offered as filters on the public page, in order.
     *
     * @return list<self>
     */
    public static function filterable(): array
    {
        return [self::Student, self::Colleague, self::Family, self::Friend];
    }

    /** Plural form for the filter links ("Students", "Family"). */
    public function plural(): string
    {
        return match ($this) {
            self::Student => 'Students',
            self::Colleague => 'Colleagues',
            self::Family => 'Family',
            self::Friend => 'Friends',
            self::Other => 'Others',
        };
    }

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
