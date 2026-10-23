@use('App\Constants\ChatCopy')
{{--
    The evergreen rail: Roman-numbered channels (the description unfolds on hover or focus and is
    always read to screen readers), unread counts, the active channel marked by a brass rule, a
    "Members" link that opens the side panel at the members, and the member's own card at the foot.
--}}
<nav class="chat-rail glow-brass on-dark" aria-labelledby="channels-heading" data-chat-rail>
    <div class="px-6 pt-6">
        <p id="channels-heading" class="t-label text-accent-text">{{ ChatCopy::CHANNELS_HEADING }}</p>
        <ol class="chat-rail__list" data-channels>
            <template x-for="channel in channels" :key="channel.id">
                <li>
                    <a :href="channelUrl(channel)" class="chat-rail__link" :aria-current="channel.current ? 'page' : null" :data-channel="channel.slug">
                        <span class="chat-rail__numeral" x-text="channel.numeral" aria-hidden="true"></span>
                        <span class="chat-rail__text">
                            <span class="chat-rail__name" x-text="channel.name"></span>
                            <span class="chat-rail__description" x-show="channel.description" x-text="channel.description" data-channel-description></span>
                        </span>
                        <span class="chat-rail__badge" x-show="channel.unread > 0" x-text="channel.unread" :aria-label="channel.unread + ' unread'" data-unread></span>
                    </a>
                </li>
            </template>
        </ol>

        <button type="button" class="chat-rail__members" x-on:click="showMembers()" aria-controls="chat-panel" data-members-link>
            <x-icon name="users" class="size-4" />
            <span>{{ ChatCopy::MEMBERS_LINK }}</span>
            <span class="chat-rail__members-count" x-text="members.length"></span>
        </button>
    </div>

    <div class="chat-rail__self" data-chat-self>
        <span class="chat-avatar chat-avatar--large" aria-hidden="true">{{ \App\Support\Initials::of(auth()->user()->name) }}</span>
        <span class="min-w-0">
            <span class="t-label block text-accent-text">{{ auth()->user()->isAdmin() ? ChatCopy::STATUS_ADMIN : ChatCopy::STATUS_MEMBER }}</span>
            <span class="smallcaps chat-rail__self-name">{{ auth()->user()->name }}</span>
            @if (filled(auth()->user()->affiliation))
                <span class="block truncate text-sm font-light text-ink">{{ auth()->user()->affiliation }}</span>
            @endif
        </span>
    </div>
</nav>
