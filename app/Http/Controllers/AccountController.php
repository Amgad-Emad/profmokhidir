<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AuthenticationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * The signed-in user's own account: name, email, password and two-factor
 * authentication. The forms post to Fortify's routes.
 */
class AccountController extends Controller
{
    public function __construct(private readonly AuthenticationService $auth) {}

    public function show(Request $request): View
    {
        $user = $request->user();

        return view('account.show', [
            'user' => $user,
            'isStaff' => $this->auth->isStaff($user),
            'twoFactor' => $this->auth->twoFactorState($user),
        ]);
    }
}
