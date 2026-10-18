<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Constants\Environments;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hides developer-only pages outside the local environment by answering 404,
 * so their existence is not revealed in production.
 */
class EnsureLocalEnvironment
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(app()->environment(Environments::developer()), 404);

        return $next($request);
    }
}
