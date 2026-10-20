<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Session "status" keys flashed by Fortify (and by our two-factor middleware),
 * with the sentence shown for each on the account and auth pages.
 */
final class AuthStatus
{
    public const string PROFILE_UPDATED = 'profile-information-updated';

    public const string PASSWORD_UPDATED = 'password-updated';

    public const string TWO_FACTOR_ENABLED = 'two-factor-authentication-enabled';

    public const string TWO_FACTOR_CONFIRMED = 'two-factor-authentication-confirmed';

    public const string TWO_FACTOR_DISABLED = 'two-factor-authentication-disabled';

    public const string RECOVERY_CODES_GENERATED = 'recovery-codes-generated';

    public const string VERIFICATION_LINK_SENT = 'verification-link-sent';

    /** Flashed by EnsureTwoFactorIsEnabled when an admin still has to set up TOTP. */
    public const string TWO_FACTOR_REQUIRED = 'two-factor-required';

    /** Flashed by EnsureAccountIsActive when a deactivated account is signed out. */
    public const string ACCOUNT_DEACTIVATED = 'account-deactivated';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::messages());
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            self::PROFILE_UPDATED => 'Your details have been saved.',
            self::PASSWORD_UPDATED => 'Your password has been changed.',
            self::TWO_FACTOR_ENABLED => 'Scan the code with your authenticator app, then enter a code to finish.',
            self::TWO_FACTOR_CONFIRMED => 'Two-factor authentication is on. Store your recovery codes somewhere safe.',
            self::TWO_FACTOR_DISABLED => 'Two-factor authentication has been turned off.',
            self::RECOVERY_CODES_GENERATED => 'New recovery codes have been generated. The old ones no longer work.',
            self::VERIFICATION_LINK_SENT => 'A new verification link has been sent to your email address.',
            self::TWO_FACTOR_REQUIRED => 'Administrator accounts must use two-factor authentication. Set up your authenticator app below before opening the dashboard.',
            self::ACCOUNT_DEACTIVATED => 'This account has been deactivated. Contact an administrator.',
        ];
    }

    public static function message(string $status): ?string
    {
        return self::messages()[$status] ?? null;
    }

    /**
     * The tone a status is shown in: bronze when the user still has to act.
     */
    public static function tone(string $status): StatusTone
    {
        return match ($status) {
            self::TWO_FACTOR_REQUIRED, self::TWO_FACTOR_ENABLED => StatusTone::Bronze,
            self::TWO_FACTOR_DISABLED, self::ACCOUNT_DEACTIVATED => StatusTone::Ash,
            default => StatusTone::Cypress,
        };
    }

    public static function isValid(string $status): bool
    {
        return array_key_exists($status, self::messages());
    }
}
