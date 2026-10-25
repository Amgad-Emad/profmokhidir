<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Timings, limits, browser event names and copy of the admin shell: autosave,
 * toasts with undo, the command palette, the shortcuts sheet and the sidebar
 * icons. Shared by the Livewire screens, the layout and resources/js/admin.
 */
final class AdminUx
{
    /** Drafts are autosaved this often while they have unsaved changes (and when a field is left). */
    public const int AUTOSAVE_SECONDS = 30;

    /** How long "Undo" stays on the toast after moving something to the trash. */
    public const int UNDO_SECONDS = 10;

    /** How long an ordinary toast stays before it fades. */
    public const int TOAST_SECONDS = 6;

    /** Drafts untouched for longer than this are listed under "Needs your attention". */
    public const int STALE_DRAFT_DAYS = 14;

    /** Revisions kept per record; older ones are pruned on save. */
    public const int REVISIONS_KEPT = 50;

    /** Revisions listed in the version history panel. */
    public const int REVISIONS_SHOWN = 20;

    /** Content matches shown per type in the command palette. */
    public const int PALETTE_RESULTS_PER_TYPE = 4;

    /** Shortest search term the palette sends to the server. */
    public const int SEARCH_MIN_LENGTH = 2;

    /** Records listed per attention card on the dashboard. */
    public const int ATTENTION_ITEMS = 5;

    public const string TOAST_SUCCESS = 'success';

    public const string TOAST_ERROR = 'error';

    public const string TOAST_INFO = 'info';

    /** Browser event a Livewire screen dispatches to show a toast. */
    public const string EVENT_TOAST = 'admin-toast';

    /** Browser event dispatched after a form is saved (clears "Unsaved changes"). */
    public const string EVENT_SAVED = 'admin-saved';

    /** Browser event telling the edit screen its form changed on the server (an earlier version was loaded). */
    public const string EVENT_CHANGED = 'admin-changed';

    /** Browser event asking the edit screen to replace its address after an autosave created the record. */
    public const string EVENT_ADDRESS = 'admin-address';

    /** Browser event carrying a fresh preview link to open. */
    public const string EVENT_PREVIEW = 'admin-preview';

    /** Route of the command palette's JSON search. */
    public const string SEARCH_ROUTE = 'admin.search';

    public const string SEARCH_PATH = 'search';

    public const string DASHBOARD_LABEL = 'Dashboard';

    /** The dashboard card recommending two-factor authentication to admins who have not turned it on. */
    public const string TWO_FACTOR_REMINDER_ROUTE = 'admin.two-factor-reminder.dismiss';

    public const string TWO_FACTOR_REMINDER_PATH = 'two-factor-reminder';

    public const string TWO_FACTOR_REMINDER_TITLE = 'Protect your account with two-factor authentication';

    public const string TWO_FACTOR_REMINDER_TEXT = 'Recommended for administrator accounts: signing in also asks for a six-digit code from an authenticator app, so a stolen password alone cannot open the dashboard.';

    public const string TWO_FACTOR_REMINDER_ACTION = 'Set up two-factor';

    public const string TWO_FACTOR_REMINDER_DISMISS = 'Not now';

    /**
     * The shortcuts sheet ("?"): keys as shown, and what they do.
     *
     * @return list<array{keys: list<string>, label: string}>
     */
    public static function shortcuts(): array
    {
        return [
            ['keys' => ['Ctrl', 'K'], 'label' => 'Open the command palette: go to any screen, start something new, find content by title'],
            ['keys' => ['/'], 'label' => 'Search this list, or open the command palette'],
            ['keys' => ['Ctrl', 'S'], 'label' => 'Save (a draft stays a draft)'],
            ['keys' => ['Ctrl', 'Enter'], 'label' => 'Publish, or update what is already published'],
            ['keys' => ['?'], 'label' => 'Show these shortcuts'],
            ['keys' => ['Esc'], 'label' => 'Close a menu, drawer or dialog'],
        ];
    }

    /**
     * Sidebar icon of each navigation item (and the dashboard), used when the
     * sidebar is collapsed to icons and in the command palette.
     *
     * @return array<string, string>
     */
    public static function icons(): array
    {
        return [
            'dashboard' => 'home',
            AdminNavigation::PAGES => 'pencil',
            AdminNavigation::PROFILE => 'user',
            AdminNavigation::CV => 'file',
            AdminNavigation::PUBLICATIONS => 'book',
            AdminNavigation::AWARDS => 'award',
            AdminNavigation::COMMENTARY => 'feather',
            AdminNavigation::TEACHING => 'layers',
            AdminNavigation::OTHER => 'info',
            AdminNavigation::HOME_QUOTES => 'quote',
            AdminNavigation::VIDEOS => 'video',
            AdminNavigation::ALBUMS => 'image',
            AdminNavigation::MEDIA_LIBRARY => 'grid',
            AdminNavigation::APPLICATIONS => 'users',
            AdminNavigation::MEMBERS => 'users',
            AdminNavigation::CHAT_REPORTS => 'chat',
            AdminNavigation::TRIBUTES => 'heart',
            AdminNavigation::ANALYTICS => 'chart',
            AdminNavigation::USERS => 'shield',
            AdminNavigation::SITE_SETTINGS => 'settings',
            AdminNavigation::ACCOUNT => 'user',
        ];
    }

    public static function icon(string $key): string
    {
        return self::icons()[$key] ?? 'arrow';
    }

    /**
     * Toast payload for a Livewire dispatch: message, tone and an optional undo
     * (the component id, the method to call and its arguments).
     *
     * @param  array{component: string, method: string, args: list<mixed>}|null  $undo
     * @return array{message: string, tone: string, undo: array{component: string, method: string, args: list<mixed>}|null, seconds: int}
     */
    public static function toast(string $message, string $tone = self::TOAST_SUCCESS, ?array $undo = null): array
    {
        return [
            'message' => $message,
            'tone' => $tone,
            'undo' => $undo,
            'seconds' => $undo === null ? self::TOAST_SECONDS : self::UNDO_SECONDS,
        ];
    }
}
