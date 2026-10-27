<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The sign-in experience: the Fortify screens (login, forgot and reset password,
 * email verification, two-factor challenge, confirm password) and the community
 * signup form. Each screen has a title, an intro, its primary action and the
 * quiet loading label shown while the form submits. The fixed copy of the
 * evergreen panel (the epigraph and the closing link) lives here too, with the
 * measurements the stylesheet mirrors (resources/css/auth.css).
 */
final class AuthScreens
{
    public const string LOGIN = 'login';

    public const string FORGOT_PASSWORD = 'forgot-password';

    public const string RESET_PASSWORD = 'reset-password';

    public const string VERIFY_EMAIL = 'verify-email';

    public const string CONFIRM_PASSWORD = 'confirm-password';

    public const string TWO_FACTOR = 'two-factor';

    public const string COMMUNITY_SIGNUP = 'community-signup';

    /** The epigraph on the evergreen panel, in the quote style. */
    public const string EPIGRAPH = Taglines::ABOUT_EPIGRAPH;

    /** The small label above the epigraph. */
    public const string PANEL_LABEL = 'The reading room';

    /** Every screen ends with this link to the public site. */
    public const string BACK_LABEL = 'Back to the website';

    /** The form column on the ivory side. */
    public const int MEASURE_PX = 400;

    /* Field sizes, focus, the password toggle's wording and the error summary are App\Constants\FormControls. */

    /** The authenticator code is six digits, one box each. */
    public const int CODE_LENGTH = 6;

    /** Password strength is judged by length alone (Password::default() asks for eight). */
    public const int PASSWORD_MIN_LENGTH = 8;

    public const int PASSWORD_GOOD_LENGTH = 12;

    public const int PASSWORD_STRONG_LENGTH = 16;

    public const string PASSWORD_HINT = 'Length matters more than symbols. A passphrase of twelve characters or more is strong.';

    /** The one wording for a failed sign-in, so accounts cannot be probed. */
    public const string FAILED_CREDENTIALS = 'These credentials do not match our records.';

    public const string REMEMBER_LABEL = 'Keep me signed in';

    public const string FORGOT_LINK = 'Forgotten your password?';

    public const string USE_RECOVERY_CODE = 'Use a recovery code instead';

    public const string USE_AUTHENTICATOR_CODE = 'Use an authenticator code instead';

    /** Autocomplete tokens, so browsers and password managers fill the right field. */
    public const string AUTOCOMPLETE_USERNAME = 'username';

    public const string AUTOCOMPLETE_CURRENT_PASSWORD = 'current-password';

    public const string AUTOCOMPLETE_NEW_PASSWORD = 'new-password';

    public const string AUTOCOMPLETE_ONE_TIME_CODE = 'one-time-code';

    /**
     * Screen key => title (also the <title>), intro, primary action and its loading label,
     * in the current language (the English source is the translation key).
     *
     * @return array<string, array{title: string, intro: string, action: string, busy: string}>
     */
    public static function screens(): array
    {
        return [
            self::LOGIN => [
                'title' => __('Sign in'),
                'intro' => __('Members of the Scientific Community and the editorial staff sign in here.'),
                'action' => __('Sign in'),
                'busy' => __('Signing in…'),
            ],
            self::FORGOT_PASSWORD => [
                'title' => __('Reset your password'),
                'intro' => __('Enter your email address and we will send you a link to choose a new password.'),
                'action' => __('Email me a reset link'),
                'busy' => __('Sending…'),
            ],
            self::RESET_PASSWORD => [
                'title' => __('Choose a new password'),
                'intro' => __('Choose a password you use nowhere else.'),
                'action' => __('Save password'),
                'busy' => __('Saving…'),
            ],
            self::VERIFY_EMAIL => [
                'title' => __('Verify your email'),
                'intro' => __('Before continuing, please confirm your email address using the link we sent you. Did not receive it? We can send another.'),
                'action' => __('Resend verification email'),
                'busy' => __('Sending…'),
            ],
            self::CONFIRM_PASSWORD => [
                'title' => __('Confirm your password'),
                'intro' => __('This is a secure area. Please confirm your password before continuing.'),
                'action' => __('Confirm'),
                'busy' => __('Confirming…'),
            ],
            self::TWO_FACTOR => [
                'title' => __('Two-factor authentication'),
                'intro' => __('Enter the six-digit code from your authenticator app.'),
                'action' => __('Continue'),
                'busy' => __('Checking…'),
            ],
            self::COMMUNITY_SIGNUP => [
                'title' => __('Apply to join'),
                'intro' => __('Tell us a little about yourself. An administrator reviews every application.'),
                'action' => __('Apply to join'),
                'busy' => __('Sending your application…'),
            ],
        ];
    }

    /**
     * @return array{title: string, intro: string, action: string, busy: string}
     */
    public static function screen(string $key): array
    {
        return self::screens()[$key] ?? throw new \InvalidArgumentException("Unknown auth screen [{$key}].");
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::screens());
    }

    /** The intro shown once the challenge has switched to a recovery code. */
    /** The label of every sign-out button (<x-account.sign-out>). */
    public const string SIGN_OUT = 'Sign out';

    public const string TWO_FACTOR_RECOVERY_INTRO = 'Enter one of the recovery codes you stored when you set up two-factor authentication.';

    /**
     * Password strength by length alone, lowest first: the minimum length the
     * level starts at and the word shown for it.
     *
     * @return list<array{key: string, from: int, label: string}>
     */
    public static function passwordStrengthLevels(): array
    {
        return [
            ['key' => 'short', 'from' => 0, 'label' => __('Too short')],
            ['key' => 'fair', 'from' => self::PASSWORD_MIN_LENGTH, 'label' => __('Fair')],
            ['key' => 'good', 'from' => self::PASSWORD_GOOD_LENGTH, 'label' => __('Good')],
            ['key' => 'strong', 'from' => self::PASSWORD_STRONG_LENGTH, 'label' => __('Strong')],
        ];
    }

    /**
     * The strength level for a password of this length.
     *
     * @return array{key: string, from: int, label: string}
     */
    public static function passwordStrength(int $length): array
    {
        $current = self::passwordStrengthLevels()[0];

        foreach (self::passwordStrengthLevels() as $level) {
            if ($length >= $level['from']) {
                $current = $level;
            }
        }

        return $current;
    }
}
