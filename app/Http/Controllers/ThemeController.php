<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Constants\Theme;
use App\Constants\ThemeSettings;
use App\Http\Requests\UpdateThemeRequest;
use App\Services\ThemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Stores the visitor's theme choice (System, Light or Dark): on the account
 * when signed in, in a cookie otherwise. Answers JSON to the theme control
 * and redirects back for the plain form on the account page.
 */
class ThemeController extends Controller
{
    public function __construct(private readonly ThemeService $themes) {}

    public function update(UpdateThemeRequest $request): JsonResponse|RedirectResponse
    {
        $theme = Theme::from($request->validated(ThemeSettings::FIELD));
        $cookie = $this->themes->remember($theme, $request->user());

        if ($request->expectsJson()) {
            return response()->json(['theme' => $theme->value])->withCookie($cookie);
        }

        return back()->withCookie($cookie);
    }
}
