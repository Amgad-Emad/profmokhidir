<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Session "status" keys flashed by Fortify (and by our sign-out response),
 * with the sentence shown for each on the account, auth and home pages.
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

    /** Flashed by App\Http\Responses\LogoutResponse; the home page shows it once. */
    public const string SIGNED_OUT = 'signed-out';

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
            self::PROFILE_UPDATED => __('Your details have been saved.'),
            self::PASSWORD_UPDATED => __('Your password has been changed.'),
            self::TWO_FACTOR_ENABLED => __('Scan the code with your authenticator app, then enter a code to finish.'),
            self::TWO_FACTOR_CONFIRMED => __('Two-factor authentication is on. Store your recovery codes somewhere safe.'),
            self::TWO_FACTOR_DISABLED => __('Two-factor authentication has been turned off.'),
            self::RECOVERY_CODES_GENERATED => __('New recovery codes have been generated. The old ones no longer work.'),
            self::VERIFICATION_LINK_SENT => __('A new verification link has been sent to your email address.'),
            self::SIGNED_OUT => __('You have signed out.'),
            self::ACCOUNT_DEACTIVATED => __('This account has been deactivated. Contact an administrator.'),
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
            self::TWO_FACTOR_ENABLED => StatusTone::Bronze,
            self::TWO_FACTOR_DISABLED, self::ACCOUNT_DEACTIVATED, self::SIGNED_OUT => StatusTone::Ash,
            default => StatusTone::Cypress,
        };
    }

    public static function isValid(string $status): bool
    {
        return array_key_exists($status, self::messages());
    }
}
