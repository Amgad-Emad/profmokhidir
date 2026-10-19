<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\CommunityPageService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Only approved community members (and admins, who moderate) may open the
 * chat. Everyone else is sent to the community page, which explains their
 * standing. Livewire update requests get a plain 403 instead so the chat
 * script can show the suspension notice without leaving the page. Checked on
 * every request, so a suspension takes effect immediately.
 */
class EnsureCommunityMember
{
    public const string ALIAS = 'community.member';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->guest(route('login'));
        }

        $user->unsetRelation('roles');

        if (! $user->canAccessChat()) {
            if ($request->hasHeader('X-Livewire') || $request->expectsJson()) {
                abort(403, 'The community chat is only open to approved members.');
            }

            return redirect()->route(CommunityPageService::ROUTE_INDEX);
        }

        return $next($request);
    }
}
