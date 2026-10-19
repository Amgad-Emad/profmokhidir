<?php

use App\Constants\ChatSettings;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Private per-channel feeds for the community chat. Only members who may use
// the chat (approved members and admins) can subscribe; the same check guards
// every poll and send, so switching to Reverb changes nothing else.
Broadcast::channel(ChatSettings::BROADCAST_CHANNEL_PREFIX.'{channelId}', function (User $user, int $channelId): bool {
    return $user->canAccessChat();
});
