<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Brand;
use App\Constants\Theme;
use App\Constants\ThemeSettings;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Cookie\Factory as CookieFactory;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * The colour theme, Light or Dark: which one a request has chosen (the
 * signed-in user's choice, else the cookie, else none, when the device
 * preference decides), how a change is kept (on the user and in a cookie the
 * inline script can read), what the layouts print on <html> and in the
 * theme-color meta so the right scheme paints first, and the state of the
 * one-button toggle.
 */
class ThemeService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly CookieFactory $cookies,
    ) {}

    /**
     * The theme chosen for a request: the user's saved choice wins over the cookie;
     * null when nothing has been chosen (or the cookie holds the retired "system").
     */
    public function current(Request $request): ?Theme
    {
        $user = $request->user() ?? auth()->user();

        if ($user instanceof User && $user->theme instanceof Theme) {
            return $user->theme;
        }

        return Theme::tryFromValue($request->cookie(ThemeSettings::COOKIE));
    }

    /**
     * Keep a choice: on the account when signed in, and always in the cookie
     * (returned for the response to queue). The cookie is readable by script so
     * the inline head script can mirror it to localStorage.
     */
    public function remember(Theme $theme, ?User $user = null): Cookie
    {
        if ($user instanceof User) {
            $this->users->updateTheme($user, $theme);
        }

        return $this->cookie($theme);
    }

    public function cookie(Theme $theme): Cookie
    {
        return $this->cookies->make(
            name: ThemeSettings::COOKIE,
            value: $theme->value,
            minutes: ThemeSettings::COOKIE_MINUTES,
            httpOnly: false,
            sameSite: 'lax',
        );
    }

    /**
     * Attributes for <html>: data-theme once a theme has been chosen; nothing before, so
     * prefers-color-scheme decides.
     */
    public function htmlAttributes(?Theme $theme): HtmlString
    {
        return new HtmlString($theme === null ? '' : sprintf('%s="%s"', ThemeSettings::ATTRIBUTE, $theme->value));
    }

    /**
     * The browser chrome colour for each scheme (brand token names, resolved to hex).
     *
     * @return array{light: string, dark: string}
     */
    public function themeColours(string $light = 'evergreen', string $dark = 'evergreen'): array
    {
        $palette = Brand::colours();

        return [
            'light' => $palette[$light] ?? Brand::EVERGREEN,
            'dark' => $palette[$dark] ?? Brand::EVERGREEN,
        ];
    }

    /**
     * The browser chrome colour for each scheme under a colour theme (Admin › Appearance):
     * the layout's brand names map to theme tokens (ThemeSettings::CHROME_TOKENS), resolved
     * in that scheme; translucent values are never used, the brand colour stands in.
     *
     * @param  array{light: array<string, string>, dark: array<string, string>}  $palette
     * @return array{light: string, dark: string}
     */
    public function chromeColours(array $palette, string $light = 'evergreen', string $dark = 'evergreen'): array
    {
        $brand = $this->themeColours($light, $dark);
        $pick = static function (string $name, string $scheme) use ($palette, $brand): string {
            $value = $palette[$scheme][ThemeSettings::CHROME_TOKENS[$name] ?? ''] ?? null;

            return is_string($value) && strlen($value) === 7 ? strtoupper($value) : $brand[$scheme];
        };

        return ['light' => $pick($light, 'light'), 'dark' => $pick($dark, 'dark')];
    }

    /**
     * The toggle as the server can print it. With no choice yet the server cannot see the
     * device setting, so it assumes the light theme and resources/js/theme.js corrects the
     * label, aria-pressed and the submitted value as soon as it starts.
     *
     * @return array{theme: string, next: string, label: string, pressed: string, chosen: bool}
     */
    public function toggle(?Theme $current): array
    {
        $showing = $current ?? Theme::Light;

        return [
            'theme' => $showing->value,
            'next' => $showing->opposite()->value,
            'label' => $showing->switchLabel(),
            'pressed' => $showing === Theme::Dark ? 'true' : 'false',
            'chosen' => $current !== null,
        ];
    }
}
