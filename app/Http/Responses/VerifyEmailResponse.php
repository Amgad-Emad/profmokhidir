<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Services\AuthenticationService;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;

/**
 * After confirming their email address, staff land on the dashboard and
 * community applicants on the community page.
 */
class VerifyEmailResponse implements VerifyEmailResponseContract
{
    public function __construct(private readonly AuthenticationService $auth) {}

    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        return redirect()->intended(LoginResponse::destinationFor($request->user(), $this->auth).'?verified=1');
    }
}
