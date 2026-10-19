<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Services\AuthenticationService;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

/**
 * Writes every staff sign-in (password login, two-factor challenge or
 * remember-me cookie) to the audit log.
 */
class LogAdminLogin
{
    public function __construct(
        private readonly AuthenticationService $auth,
        private readonly Request $request,
    ) {}

    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->auth->recordLogin(
            $event->user,
            $this->request->ip(),
            $this->request->userAgent(),
            $event->remember,
        );
    }
}
