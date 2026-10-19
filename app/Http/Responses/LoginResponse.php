<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Models\User;
use App\Services\AuthenticationService;
use App\Services\CommunityPageService;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

/**
 * Staff go to the dashboard after signing in; everyone else (community
 * members and applicants) goes to the community page, which shows their
 * standing or opens the chat.
 */
class LoginResponse implements LoginResponseContract
{
    public function __construct(private readonly AuthenticationService $auth) {}

    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false]);
        }

        return redirect()->intended(self::destinationFor($request->user(), $this->auth));
    }

    public static function destinationFor(mixed $user, AuthenticationService $auth): string
    {
        if ($user instanceof User && $auth->isStaff($user)) {
            return Fortify::redirects('login');
        }

        return route(CommunityPageService::ROUTE_INDEX);
    }
}
