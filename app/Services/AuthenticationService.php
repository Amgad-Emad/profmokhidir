<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AuditAction;
use App\Constants\Roles;
use App\Models\AuditLog;
use App\Models\User;

/**
 * Sign-in policy for staff accounts: admins must confirm TOTP two-factor
 * authentication before they may use the dashboard, and every staff sign-in
 * is written to the audit log.
 */
class AuthenticationService
{
    public function __construct(
        private readonly RoleService $roles,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * Whether the user holds a role that may use the admin dashboard.
     */
    public function isStaff(User $user): bool
    {
        return array_intersect(Roles::staff(), $this->roles->rolesOf($user)) !== [];
    }

    /**
     * Whether two-factor authentication is mandatory for this user (admins only).
     */
    public function requiresTwoFactor(User $user): bool
    {
        return in_array(Roles::ADMIN, $this->roles->rolesOf($user), true);
    }

    /**
     * Two-factor is enabled once a secret exists; it counts only when the user
     * has confirmed it with a valid code.
     */
    public function hasConfirmedTwoFactor(User $user): bool
    {
        return $user->two_factor_secret !== null && $user->two_factor_confirmed_at !== null;
    }

    public function hasPendingTwoFactor(User $user): bool
    {
        return $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null;
    }

    /**
     * True when the user must finish two-factor setup before using the dashboard.
     */
    public function mustSetUpTwoFactor(User $user): bool
    {
        return $this->requiresTwoFactor($user) && ! $this->hasConfirmedTwoFactor($user);
    }

    /**
     * Everything the account page needs to render the two-factor section.
     *
     * @return array{required: bool, enabled: bool, confirmed: bool, pending: bool, qr_code: ?string, setup_key: ?string, recovery_codes: list<string>}
     */
    public function twoFactorState(User $user): array
    {
        $pending = $this->hasPendingTwoFactor($user);
        $confirmed = $this->hasConfirmedTwoFactor($user);

        return [
            'required' => $this->requiresTwoFactor($user),
            'enabled' => $pending || $confirmed,
            'confirmed' => $confirmed,
            'pending' => $pending,
            'qr_code' => $pending ? $user->twoFactorQrCodeSvg() : null,
            'setup_key' => $pending ? decrypt($user->two_factor_secret) : null,
            'recovery_codes' => $pending || $confirmed ? $user->recoveryCodes() : [],
        ];
    }

    /**
     * Record a staff sign-in. Returns null for users without a staff role.
     */
    public function recordLogin(User $user, ?string $ip = null, ?string $userAgent = null, bool $remembered = false): ?AuditLog
    {
        if (! $this->isStaff($user)) {
            return null;
        }

        return $this->audit->record($user, AuditAction::ADMIN_LOGIN, $user, array_filter([
            'ip' => $ip,
            'user_agent' => $userAgent === null ? null : mb_substr($userAgent, 0, 255),
            'remembered' => $remembered ?: null,
        ], fn ($value): bool => $value !== null));
    }
}
