<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

/**
 * Where an account stands, as the Users & roles list filters it: signing in
 * normally, a staff invitation not yet taken up (no password chosen, so the
 * email is unverified), a community account that has not verified its email,
 * or deactivated.
 */
enum AccountState: string
{
    use HasValues;

    case Active = 'active';
    case Invited = 'invited';
    case Unverified = 'unverified';
    case Deactivated = 'deactivated';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('Active'),
            self::Invited => __('Invitation pending'),
            self::Unverified => __('Email unverified'),
            self::Deactivated => __('Deactivated'),
        };
    }
}
