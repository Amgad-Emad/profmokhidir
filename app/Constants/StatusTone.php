<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * How a status is coloured in the dashboard. Brand colours stand in for the
 * usual traffic lights: pending is bronze, approved/published is cypress and
 * rejected/draft/inactive is ash. Every tone also carries an icon and label,
 * so state is never shown by colour alone.
 */
enum StatusTone: string
{
    case Bronze = 'bronze';
    case Cypress = 'cypress';
    case Ash = 'ash';

    public static function forTribute(TributeStatus $status): self
    {
        return match ($status) {
            TributeStatus::Pending => self::Bronze,
            TributeStatus::Approved => self::Cypress,
            TributeStatus::Rejected => self::Ash,
        };
    }

    public static function forCommunity(CommunityStatus $status): self
    {
        return match ($status) {
            CommunityStatus::Pending => self::Bronze,
            CommunityStatus::Approved => self::Cypress,
            CommunityStatus::None, CommunityStatus::Rejected, CommunityStatus::Suspended => self::Ash,
        };
    }

    public static function forContent(ContentStatus $status): self
    {
        return match ($status) {
            ContentStatus::Published => self::Cypress,
            ContentStatus::Draft => self::Ash,
        };
    }

    /**
     * Token classes for a badge (pending, success and neutral tones follow the theme).
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Bronze => 'border-pending-tone text-pending-tone',
            self::Cypress => 'border-success-tone text-success-tone',
            self::Ash => 'border-neutral-tone text-neutral-tone',
        };
    }

    /**
     * Token text colour class for the tone.
     */
    public function textClass(): string
    {
        return match ($this) {
            self::Bronze => 'text-pending-tone',
            self::Cypress => 'text-success-tone',
            self::Ash => 'text-neutral-tone',
        };
    }

    /**
     * Name of the inline icon that accompanies the tone (see the x-icon component).
     */
    public function icon(): string
    {
        return match ($this) {
            self::Bronze => 'clock',
            self::Cypress => 'check',
            self::Ash => 'minus',
        };
    }
}
