<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ChatService;
use App\Services\CommunityPageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * /community/chat opens the first channel; the Livewire room renders each channel.
 */
class ChatController extends Controller
{
    public function __invoke(ChatService $chat): RedirectResponse|View
    {
        $channel = $chat->firstChannel();

        if ($channel === null) {
            return view('community.chat.empty', [
                'communityUrl' => route(CommunityPageService::ROUTE_INDEX),
            ]);
        }

        return redirect()->route(CommunityPageService::ROUTE_CHAT.'.channel', $channel);
    }
}
