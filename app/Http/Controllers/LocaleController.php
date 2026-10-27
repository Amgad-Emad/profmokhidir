<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Constants\Locales;
use App\Services\LocaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The language switcher: keeps the choice (on the account when signed in, in
 * a cookie always) and opens the same page in that language. The target is a
 * path on this site; the browser carries the #anchor across the redirect.
 */
class LocaleController extends Controller
{
    public function __invoke(Request $request, LocaleService $locales, string $locale): RedirectResponse
    {
        $cookie = $locales->remember($locale, $request->user());
        $target = $locales->safeTarget($request->query(Locales::TARGET_PARAM), $locale);

        return redirect($target)->withCookie($cookie);
    }
}
