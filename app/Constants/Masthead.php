<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The public masthead: measurements, motion and copy. resources/css/masthead.css
 * and resources/js/masthead.js implement these values; tests read them from here.
 */
final class Masthead
{
    /** Bar height at rest and once compacted on scroll (CSS pixels). */
    public const int HEIGHT_PX = 88;

    public const int COMPACT_HEIGHT_PX = 64;

    /** The slim utility line above the bar on wide screens (hidden when compacted). */
    public const int UTILITY_HEIGHT_PX = 32;

    /** Tailwind breakpoint (px) from which the utility line shows. */
    public const int UTILITY_FROM_PX = 1280;

    /** Scroll distance before the bar may compact (scrolling down); scrolling up restores it. */
    public const int COMPACT_AFTER_PX = Motion::HEADER_CONDENSE_AFTER_PX;

    /** Scroll deltas smaller than this are ignored, so trackpad jitter does not flip the bar. */
    public const int SCROLL_JITTER_PX = 4;

    /** Height, lockup and glass transitions. */
    public const int TRANSITION_MS = Motion::HEADER_CONDENSE_DURATION_MS;

    /** The glass: evergreen at this opacity over a blur with light saturation; a solid fallback elsewhere. */
    public const int GLASS_OPACITY_PERCENT = 88;

    public const int GLASS_BLUR_PX = 16;

    public const int GLASS_SATURATE_PERCENT = 120;

    /** The brass hairline along the bottom of the glass. */
    public const int HAIRLINE_OPACITY_PERCENT = 35;

    /** Mega menu: fades and rises this far over this long; instantly under reduced motion. */
    public const int MENU_RISE_PX = 8;

    public const int MENU_DURATION_MS = 400;

    /** Hover opens a chapter on fine pointers after this delay, and closes it after leaving for this long. */
    public const int HOVER_OPEN_DELAY_MS = 150;

    public const int HOVER_CLOSE_DELAY_MS = 300;

    /** Copy. */
    public const string NAV_LABEL = 'Primary';

    public const string UTILITY_LINE = 'In honour of Professor Mohamed Osman Khidir';

    public const string SIGN_IN = 'Community sign in';

    public const string OPEN_CHAT = 'Open the chat';

    public const string ACCOUNT = 'Your account';

    /** The quiet link a signed-out visitor sees in the bar. */
    public const string SIGN_IN_SHORT = 'Sign in';

    /** The account menu of a signed-in user (initials avatar in the bar). */
    public const string ACCOUNT_MENU_LABEL = 'Account menu';

    public const string ACCOUNT_MENU_ID = 'site-account-menu';

    public const string ACCOUNT_MENU_CHAT = 'Community chat';

    public const string ACCOUNT_MENU_DASHBOARD = 'Dashboard';

    public const string ACCOUNT_MENU_THEME = 'Appearance';

    public const string SIGNED_IN_AS = 'Signed in as';

    public const string LEAVE_TRIBUTE = 'Leave a tribute';

    public const string MENU = 'Menu';

    public const string CLOSE = 'Close';

    public const string CONTENTS = 'Contents';

    public const string MENU_DIALOG_LABEL = 'Site menu';

    /** Element ids the script and the tests share. */
    public const string MENU_ID = 'site-menu';

    public const string SEARCH_ID = 'site-search';

    public const string SEARCH_OPENER_ID = 'site-search-opener';

    /** Attribute on the home hero, so the bar knows when it is over it. */
    public const string HERO_ATTRIBUTE = 'data-masthead-hero';

    /** Classes written on <html> by the script. */
    public const string COMPACT_CLASS = 'header-compact';

    public const string OVER_HERO_CLASS = 'over-hero';

    public static function chapterPanelId(string $chapter): string
    {
        return 'chapter-'.$chapter;
    }

    public static function chapterButtonId(string $chapter): string
    {
        return 'chapter-'.$chapter.'-button';
    }

    public static function mobileChapterPanelId(string $chapter): string
    {
        return 'menu-chapter-'.$chapter;
    }
}
