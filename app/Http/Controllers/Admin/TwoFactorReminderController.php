<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Constants\AdminNavigation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DismissTwoFactorReminderRequest;
use App\Services\AuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * "Not now" on the dashboard's two-factor card: remembered per user, so the
 * card stays hidden on every later visit.
 */
class TwoFactorReminderController extends Controller
{
    public function __construct(private readonly AuthenticationService $auth) {}

    public function __invoke(DismissTwoFactorReminderRequest $request): RedirectResponse|JsonResponse
    {
        $this->auth->dismissTwoFactorReminder($request->user());

        if ($request->wantsJson()) {
            return new JsonResponse(['dismissed' => true]);
        }

        return redirect()->route(AdminNavigation::DASHBOARD_ROUTE);
    }
}
