<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ColourTheme;
use App\Services\ColourThemeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * "Preview on site": a signed link that shows a theme's draft across the live
 * site to the admin who opened it (kept in their session; nobody else sees
 * it), and the banner's "End preview".
 */
class AppearancePreviewController extends Controller
{
    public function __construct(private readonly ColourThemeService $themes) {}

    public function show(Request $request, ColourTheme $theme): RedirectResponse
    {
        Gate::authorize('view', $theme);
        $this->themes->startPreview($theme);

        return redirect()->route('home');
    }

    public function end(Request $request): RedirectResponse
    {
        $this->themes->endPreview();
        $back = $request->query('to');

        return is_string($back) && str_starts_with($back, '/') && ! str_starts_with($back, '//') ? redirect($back) : redirect()->route('home');
    }
}
