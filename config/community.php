<?php

use App\Constants\ChatSettings;
use App\Constants\ChatTransport;

return [

    /*
    |--------------------------------------------------------------------------
    | Community chat transport
    |--------------------------------------------------------------------------
    |
    | "poll" refreshes the chat with wire:poll and needs nothing else.
    | "broadcast" additionally broadcasts ChatMessageChanged over the configured
    | broadcaster (Laravel Reverb) so clients are nudged immediately; install
    | Reverb and Echo, set BROADCAST_CONNECTION=reverb and switch this flag.
    | Clients always fetch from their own cursor, so the chat rules are the same
    | under both transports.
    |
    */

    'chat' => [
        'transport' => env('COMMUNITY_CHAT_TRANSPORT', ChatTransport::default()->value),
        'poll_interval' => (int) env('COMMUNITY_CHAT_POLL_INTERVAL', ChatSettings::POLL_INTERVAL_SECONDS),
    ],

];
