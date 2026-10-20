<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * A staff-account change that the safeguards refuse. The message is safe to
 * show to the administrator.
 */
class UserManagementException extends RuntimeException
{
    public static function cannotChangeOwnRole(): self
    {
        return new self('You cannot change your own role. Ask another administrator.');
    }

    public static function cannotDeactivateSelf(): self
    {
        return new self('You cannot deactivate your own account. Ask another administrator.');
    }

    public static function lastActiveAdmin(): self
    {
        return new self('This is the last active administrator. Make someone else an administrator first.');
    }

    public static function invitationAlreadyAccepted(): self
    {
        return new self('This person has already set up their account, so there is no invitation to resend.');
    }

    public static function notStaffRole(string $role): self
    {
        return new self(sprintf('"%s" is not a staff role. Invite people as administrators or editors.', $role));
    }
}
