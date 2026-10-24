<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Constants\AuthStatus;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

/**
 * After signing out (from the public header, the chat header, the admin top bar or
 * sidebar, or the account page) everyone lands on the home page, which shows a quiet
 * "You have signed out" notice once.
 */
class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        return redirect()->route('home')->with('status', AuthStatus::SIGNED_OUT);
    }
}
