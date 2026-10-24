<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Analytics;
use App\Constants\Permissions;
use App\Constants\SettingKeys;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Whether the first-party analytics is on (the "Analytics" site setting, off
 * by default) and whether a given request may be recorded at all: never for
 * signed-in staff, never when the browser asks not to be tracked (Do Not
 * Track or Global Privacy Control), never on the dashboard, the members' chat
 * or the sign-in screens.
 */
class AnalyticsService
{
    public function __construct(private readonly SettingService $settings) {}

    public function enabled(): bool
    {
        return $this->settings->get(SettingKeys::ANALYTICS_ENABLED) === '1';
    }

    public function isStaff(?User $user): bool
    {
        return $user !== null && $user->can(Permissions::ACCESS_ADMIN);
    }

    /**
     * Do Not Track ("DNT: 1") or Global Privacy Control ("Sec-GPC: 1").
     */
    public function optedOut(Request $request): bool
    {
        return $request->header('DNT') === '1' || $request->header('Sec-GPC') === '1';
    }

    /**
     * Whether a public page should carry the analytics script.
     */
    public function shouldLoadScript(Request $request): bool
    {
        return $this->enabled()
            && ! $this->isStaff($request->user())
            && ! $this->optedOut($request)
            && ! Analytics::isExcludedPath('/'.ltrim($request->path(), '/'));
    }

    /**
     * What the script reads from its <meta> tag: only the collector's path, so the tag stays
     * small (the idle window and scroll marks are constants in the script itself).
     */
    public function endpoint(): string
    {
        return route(Analytics::COLLECT_ROUTE, absolute: false);
    }
}
