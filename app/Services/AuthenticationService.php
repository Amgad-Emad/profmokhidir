<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AuditAction;
use App\Constants\Roles;
use App\Models\AuditLog;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

/**
 * Sign-in policy for staff accounts: two-factor authentication is available to
 * every account and recommended (never required) for administrators, who see a
 * dismissible dashboard card until they turn it on; every staff sign-in is
 * written to the audit log.
 */
class AuthenticationService
{
    public function __construct(
        private readonly RoleService $roles,
        private readonly AuditLogService $audit,
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * Whether the user holds a role that may use the admin dashboard.
     */
    public function isStaff(User $user): bool
    {
        return array_intersect(Roles::staff(), $this->roles->rolesOf($user)) !== [];
    }

    /**
     * Whether two-factor authentication is recommended for this user (admins). It is
     * never enforced: any account may turn it on or off on the account page.
     */
    public function recommendsTwoFactor(User $user): bool
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
     * Whether the dashboard shows the "Protect your account" card: an admin without
     * confirmed two-factor who has not dismissed it.
     */
    public function shouldRemindTwoFactor(User $user): bool
    {
        return $this->recommendsTwoFactor($user)
            && ! $this->hasConfirmedTwoFactor($user)
            && $user->two_factor_reminder_dismissed_at === null;
    }

    /**
     * Hide the two-factor card for this user from now on.
     */
    public function dismissTwoFactorReminder(User $user): User
    {
        return $this->users->dismissTwoFactorReminder($user);
    }

    /**
     * Everything the account page needs to render the two-factor section.
     *
     * @return array{recommended: bool, enabled: bool, confirmed: bool, pending: bool, qr_code: ?string, setup_key: ?string, recovery_codes: list<string>}
     */
    public function twoFactorState(User $user): array
    {
        $pending = $this->hasPendingTwoFactor($user);
        $confirmed = $this->hasConfirmedTwoFactor($user);

        return [
            'recommended' => $this->recommendsTwoFactor($user),
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
