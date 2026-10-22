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
 * The colour theme: which one applies to a request (the signed-in user's
 * choice, else the cookie, else System), how a change is kept (on the user
 * and in a cookie the inline script can read) and what the layouts print on
 * <html> and in the theme-color meta so the right scheme paints first.
 */
class ThemeService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly CookieFactory $cookies,
    ) {}

    /**
     * The theme in force for a request: the user's saved choice wins over the cookie.
     */
    public function current(Request $request): Theme
    {
        $user = $request->user() ?? auth()->user();

        if ($user instanceof User && $user->theme instanceof Theme) {
            return $user->theme;
        }

        return Theme::tryFromValue($request->cookie(ThemeSettings::COOKIE)) ?? Theme::DEFAULT;
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
     * Attributes for <html>: the stored preference, and data-theme when it forces a scheme.
     */
    public function htmlAttributes(Theme $theme): HtmlString
    {
        $attributes = sprintf('%s="%s"', ThemeSettings::PREFERENCE_ATTRIBUTE, $theme->value);

        if ($theme->isExplicit()) {
            $attributes .= sprintf(' %s="%s"', ThemeSettings::ATTRIBUTE, $theme->value);
        }

        return new HtmlString($attributes);
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
     * The three choices for a control, in display order.
     *
     * @return list<array{value: string, label: string, description: string, icon: string, current: bool}>
     */
    public function options(Theme $current): array
    {
        return array_map(static fn (Theme $theme): array => [
            'value' => $theme->value,
            'label' => $theme->label(),
            'description' => $theme->description(),
            'icon' => $theme->icon(),
            'current' => $theme === $current,
        ], Theme::cases());
    }
}
