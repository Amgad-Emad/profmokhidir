<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Constants\Locales;
use App\Services\LocaleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the language of the request. Registered twice: globally with the
 * `path` mode, so even an address no route matches (the 404 page) speaks the
 * language of its prefix; and in the web group, where the matched route
 * decides (a public page's own language, else the saved choice).
 */
class SetLocale
{
    public const string PATH_MODE = 'path';

    public function __construct(private readonly LocaleService $locales) {}

    public function handle(Request $request, Closure $next, ?string $mode = null): Response
    {
        $locale = $mode === self::PATH_MODE
            ? Locales::fromPath($request->path())
            : $this->locales->resolve($request);

        $this->locales->apply($locale);

        return $next($request);
    }
}
