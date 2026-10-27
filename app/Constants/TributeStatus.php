<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

enum TributeStatus: string
{
    use HasValues;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public static function default(): self
    {
        return self::Pending;
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending review'),
            self::Approved => __('Approved'),
            self::Rejected => __('Rejected'),
        };
    }
}
