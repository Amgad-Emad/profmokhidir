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

    /** <html data-theme="light|dark"> forces a scheme; absent, prefers-color-scheme decides. */
    public const string ATTRIBUTE = 'data-theme';

    /** <html data-theme-preference="system|light|dark">: the stored choice, for the control. */
    public const string PREFERENCE_ATTRIBUTE = 'data-theme-preference';

    /** Set on <html> for the length of the colour transition when the theme changes. */
    public const string TRANSITION_ATTRIBUTE = 'data-theme-transition';

    /** Colours cross-fade over this long when the theme changes (instantly with reduced motion). */
    public const int TRANSITION_MS = 300;

    public const string ROUTE = 'theme.update';

    public const string PATH = '/theme';

    /** Request field carrying the choice. */
    public const string FIELD = 'theme';

    /** Column on users. */
    public const string COLUMN = 'theme';

    /** Shown by the control and the account page. */
    public const string LABEL = 'Appearance';

    public const string CONTROL_LABEL = 'Change appearance';

    public const string ACCOUNT_INTRO = 'Choose how the site is lit. System follows your device; the choice is kept on this account and on this device.';

    /** Name of the meta tag that tints the browser chrome. */
    public const string META_THEME_COLOR = 'theme-color';
}
