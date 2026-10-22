@use('App\Constants\ChatCopy')
{{-- The evergreen rail: Roman-numbered channels, unread counts, and the member at the foot. --}}
<nav class="chat-rail glow-brass on-dark" aria-labelledby="channels-heading" data-chat-rail>
    <div class="px-6 pt-6">
        <p id="channels-heading" class="t-label text-accent-text">{{ ChatCopy::CHANNELS_HEADING }}</p>
        <ol class="chat-rail__list" data-channels>
            <template x-for="channel in channels" :key="channel.id">
                <li>
                    <a :href="channelUrl(channel)" class="chat-rail__link" :aria-current="channel.current ? 'page' : null" :data-channel="channel.slug">
                        <span class="chat-rail__numeral" x-text="channel.numeral" aria-hidden="true"></span>
                        <span class="chat-rail__name" x-text="channel.name"></span>
                        <span class="chat-rail__badge" x-show="channel.unread > 0" x-text="channel.unread" :aria-label="channel.unread + ' unread'" data-unread></span>
                    </a>
                </li>
            </template>
        </ol>
    </div>

    <div class="chat-rail__self" data-chat-self>
        <p class="t-label text-accent-text">{{ auth()->user()->isAdmin() ? ChatCopy::STATUS_ADMIN : ChatCopy::STATUS_MEMBER }}</p>
        <span class="smallcaps chat-rail__self-name">{{ auth()->user()->name }}</span>
        @if (filled(auth()->user()->affiliation))
            <span class="block text-sm font-light text-ink">{{ auth()->user()->affiliation }}</span>
        @endif
    </div>
</nav>
