<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Where the theme choice (App\Constants\Theme) is kept and how it reaches the
 * page: the cookie and localStorage key, the attributes on <html>, the route
 * that stores a change and the soft colour transition when it changes.
 */
final class ThemeSettings
{
    /** Cookie for guests (and a mirror for signed-in users), not HTTP-only so the inline script can read it. */
    public const string COOKIE = 'theme';

    /** A year, in minutes. */
    public const int COOKIE_MINUTES = 525600;

    /** localStorage key mirroring the cookie, read before the first paint. */
    public const string STORAGE_KEY = 'theme';

    /** <html data-theme="light|dark"> forces a scheme; absent (nothing chosen yet), prefers-color-scheme decides. */
    public const string ATTRIBUTE = 'data-theme';

    /**
     * The retired third choice. A cookie or localStorage value still holding it is
     * replaced on the client by the theme the device resolves to (and saved to the
     * account); the users column was migrated to NULL.
     */
    public const string LEGACY_SYSTEM = 'system';

    /** Set on <html> by the head script when it has just resolved a legacy value, so theme.js saves it. */
    public const string LEGACY_ATTRIBUTE = 'data-theme-resolved';

    /** Set on <html> for the length of the colour transition when the theme changes. */
    public const string TRANSITION_ATTRIBUTE = 'data-theme-transition';

    /** Colours cross-fade over this long when the theme changes (instantly with reduced motion). */
    public const int TRANSITION_MS = 300;

    public const string ROUTE = 'theme.update';

    public const string PATH = '/theme';

    /** Request field carrying the choice. */
    public const string FIELD = 'theme';

    /** Column on users (NULL until the person chooses). */
    public const string COLUMN = 'theme';

    /** The toggle's accessible name, with the theme it switches to ("Switch to dark theme"). */
    public const string SWITCH_LABEL = 'Switch to %s theme';

    /** Shown on the account page. */
    public const string LABEL = 'Appearance';

    public const string ACCOUNT_INTRO = 'Switch between the light and the dark theme. The site starts from your device setting; once you switch, the choice is kept on this account and on this device.';

    /** Name of the meta tag that tints the browser chrome. */
    public const string META_THEME_COLOR = 'theme-color';
}
