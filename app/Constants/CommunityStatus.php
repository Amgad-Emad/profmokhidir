<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

/**
 * Where a user stands with the Scientific Community. Only APPROVED users
 * (who also hold the member role) may use the private chat.
 */
enum CommunityStatus: string
{
    use HasValues;

    case None = 'none';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    public static function default(): self
    {
        return self::None;
    }

    public function label(): string
    {
        return match ($this) {
            self::None => __('Not applied'),
            self::Pending => __('Pending review'),
            self::Approved => __('Approved'),
            self::Rejected => __('Rejected'),
            self::Suspended => __('Suspended'),
        };
    }

    public function canChat(): bool
    {
        return $this === self::Approved;
    }
}
