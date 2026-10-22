@use('App\Constants\ChatCopy')
@use('App\Constants\ChatSettings')
{{-- The conversation: channel header, offline banner, search, the log and the composer. --}}
<section class="chat-room" aria-labelledby="room-heading" data-chat-conversation>
    <header class="chat-room__header">
        <button type="button" class="chat-back" x-on:click="showChannels()" data-back>
            <x-icon name="chevron-left" class="size-5" />
            <span class="t-label text-accent-text">{{ ChatCopy::BACK_TO_CHANNELS }}</span>
        </button>
        <div class="chat-room__heading">
            <h1 id="room-heading" class="chat-room__title">{{ $channelName }}</h1>
            <p class="chat-room__description" x-text="currentChannel?.description || @js($chatIntro)">{{ $initial['channels'][array_search(true, array_column($initial['channels'], 'current'), true) ?: 0]['description'] ?? $chatIntro }}</p>
        </div>
        <div class="chat-room__tools">
            <span class="chat-room__count t-label text-accent-text" data-member-count><span x-text="members.length"></span> <span x-text="members.length === 1 ? 'member' : 'members'"></span></span>
            <button type="button" class="chat-tool" x-on:click="toggleSearch()" :aria-expanded="searchOpen ? 'true' : 'false'" aria-controls="chat-search" data-search-toggle>
                <x-icon name="search" :label="ChatCopy::SEARCH_LABEL" />
            </button>
            <button type="button" class="chat-tool" x-on:click="togglePanel()" :aria-expanded="panelOpen ? 'true' : 'false'" aria-controls="chat-panel" data-panel-toggle>
                <x-icon name="panel" :label="ChatCopy::PANEL_OPEN" />
            </button>
        </div>
    </header>

    <p class="chat-banner" role="status" x-show="offline || expired" x-cloak data-offline-banner>
        <x-icon name="clock" class="size-4" />
        <span x-show="!expired">{{ ChatCopy::RECONNECTING }} <span class="hidden sm:inline">{{ ChatCopy::OFFLINE_BANNER }}</span></span>
        <span x-show="expired" x-cloak>{{ ChatCopy::EXPIRED }} <a href="{{ route('login') }}" class="text-ink underline decoration-accent-line underline-offset-4">{{ ChatCopy::SIGN_IN_AGAIN }}</a>.</span>
    </p>

    <div id="chat-search" class="chat-search" x-show="searchOpen" x-cloak x-on:keydown.escape.stop.prevent="closeSearch()" data-chat-search>
        <label for="chat-search-input" class="t-label text-accent-text">{{ ChatCopy::SEARCH_LABEL }}</label>
        <input id="chat-search-input" type="search" class="chat-search__input mt-1" placeholder="{{ ChatCopy::SEARCH_PLACEHOLDER }}" x-model="searchTerm" x-on:input.debounce.300ms="runSearch()" x-ref="searchInput" autocomplete="off" maxlength="{{ ChatSettings::SEARCH_MAX_LENGTH }}">
        <p class="mt-2 text-sm font-light text-ink-muted" role="status" x-text="searchStatus"></p>
        <ol class="chat-search__results" x-show="searchResults.length > 0" data-search-results>
            <template x-for="r in searchResults" :key="r.id">
                <li>
                    <button type="button" class="chat-search__result" x-on:click="jumpTo(r.id)">
                        <span class="smallcaps text-ink" x-text="r.author"></span>
                        <time class="chat-time ml-2" :datetime="r.created_at" x-text="formatDateTime(r.created_at)"></time>
                        <span class="chat-search__excerpt" x-text="r.body"></span>
                    </button>
                </li>
            </template>
        </ol>
    </div>

    <div class="chat-scroller" x-ref="scroller" x-on:scroll.passive="onScroll()" x-on:keydown="onKeydown($event)" tabindex="0" aria-label="Messages" data-scroller>
        <div class="py-3 text-center" x-show="hasOlder">
            <button type="button" class="chat-action" x-on:click="loadOlder()" x-bind:disabled="loadingOlder" data-load-older>
                <span x-show="!loadingOlder">{{ ChatCopy::LOAD_OLDER }}</span>
                <span x-show="loadingOlder" x-cloak>Loading…</span>
            </button>
        </div>

        <div class="chat-empty" x-show="messages.length === 0" data-empty>
            <x-epigraph :attribution="ChatCopy::EMPTY_ATTRIBUTION">{{ ChatCopy::EMPTY_EPIGRAPH }}</x-epigraph>
            <p class="chat-empty__rule"><span class="t-label text-accent-text">{{ ChatCopy::PANEL_CONDUCT }}</span><br>{{ $conduct[0] ?? '' }}</p>
        </div>

        {{-- The log wraps the list so the list keeps its own semantics; new letters are announced politely. --}}
        <div class="chat-log" role="log" aria-live="polite" aria-relevant="additions" aria-label="Conversation" data-messages>
        <ol>
            <template x-for="day in days" :key="day.key">
                <li>
                    <p class="chat-day__divider" data-day><span class="smallcaps chat-day__label" x-text="day.label"></span></p>
                    <ol>
                        <template x-for="run in day.runs" :key="run.key">
                            <li class="chat-run" :class="{ 'is-mine': run.mine }" data-run>
                                <template x-if="unreadFrom && run.messages[0].id === unreadFrom">
                                    <p class="chat-unread" data-unread-divider><span>{{ ChatCopy::NEW_DIVIDER }}</span></p>
                                </template>
                                <p class="chat-run__head">
                                    <span class="smallcaps chat-run__author" x-text="run.author"></span>
                                    <span class="t-label text-accent-text" x-show="run.affiliation" x-text="run.affiliation"></span>
                                    <time class="chat-time" :datetime="run.messages[0].created_at" x-text="formatTime(run.messages[0].created_at)"></time>
                                </p>
                                <ol>
                                    <template x-for="(m, index) in run.messages" :key="m.key">
                                        @include('livewire.community.chat.partials.message')
                                    </template>
                                </ol>
                            </li>
                        </template>
                    </ol>
                </li>
            </template>
        </ol>
        </div>
    </div>

    <button type="button" class="chat-new-pill" x-show="newCount > 0" x-cloak x-on:click="scrollToBottom(true)" data-new-messages>
        <span x-text="newCount === 1 ? config.copy.newOne : config.copy.newMany.replace(':count', newCount)"></span>
    </button>

    @include('livewire.community.chat.partials.composer')
</section>
