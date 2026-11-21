<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\SectionVisibilityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A hidden section's pages answer 404 to visitors (staff who manage content
 * still see them). Runs on the public routes in every language.
 */
class EnsureSectionVisible
{
    public function __construct(private readonly SectionVisibilityService $sections) {}

    public function handle(Request $request, Closure $next): Response
    {
        $section = $this->sections->sectionForRoute($request->route()?->getName());

        if ($section !== null && ! $this->sections->canView($section, $request->user())) {
            abort(404);
        }

        return $next($request);
    }
}
