<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Constants\AuthStatus;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * A deactivated account is signed out on its next request (web and Livewire
 * alike) and sent to the sign-in page with an explanation.
 */
class EnsureAccountIsActive
{
    public const string ALIAS = 'active';

    public const string STATUS_KEY = AuthStatus::ACCOUNT_DEACTIVATED;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->isActive()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', self::STATUS_KEY);
        }

        return $next($request);
    }
}
