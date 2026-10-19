<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Constants\AdminNavigation;
use App\Models\User;
use App\Services\AuthenticationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin accounts must have confirmed TOTP two-factor authentication before
 * they can use the dashboard. Until then every /admin request is sent to the
 * account page, where the authenticator can be set up.
 */
class EnsureTwoFactorIsEnabled
{
    public const string STATUS_KEY = 'two-factor-required';

    public function __construct(private readonly AuthenticationService $auth) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $this->auth->mustSetUpTwoFactor($user)) {
            return redirect()
                ->route(AdminNavigation::ACCOUNT_ROUTE)
                ->withFragment('two-factor')
                ->with('status', self::STATUS_KEY);
        }

        return $next($request);
    }
}
