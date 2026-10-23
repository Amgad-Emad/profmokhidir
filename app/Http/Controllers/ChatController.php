<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\CommunityPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The chat used to have channels at /community/chat/{channel}. There is one
 * room now: every old channel address moves permanently to /community/chat,
 * keeping a permalink's ?message= parameter.
 */
class ChatController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $query = $request->getQueryString();

        return redirect()->to(route(CommunityPageService::ROUTE_CHAT).($query ? '?'.$query : ''), 301);
    }
}
