@use('App\Constants\ChatCopy')
{{-- "About this channel": description, conduct in brief, pinned messages and the members. A column from 1280px, a drawer below. --}}
<div class="chat-panel__backdrop" x-show="panelOpen && !isWide" x-cloak x-on:click="closePanel()" aria-hidden="true"></div>
<aside id="chat-panel" class="chat-panel" aria-labelledby="chat-panel-title" x-show="panelOpen" x-cloak x-trap.inert.noscroll="panelOpen && !isWide" data-chat-panel>
    <div class="chat-panel__head">
        <h2 id="chat-panel-title" class="chat-panel__title">{{ ChatCopy::PANEL_TITLE }}</h2>
        <button type="button" class="chat-tool" x-on:click="closePanel()" data-panel-close>
            <x-icon name="close" :label="ChatCopy::PANEL_CLOSE" />
        </button>
    </div>

    <section class="chat-panel__section" aria-labelledby="chat-panel-about">
        <h3 id="chat-panel-about" class="t-label text-accent-text">{{ $channelName }}</h3>
        <p class="chat-panel__text mt-2" x-text="currentChannel?.description || @js($chatIntro)"></p>
        <p class="mt-2 text-sm font-light text-ink-muted">{{ $chatIntro }}</p>
    </section>

    <section class="chat-panel__section" aria-labelledby="chat-panel-conduct">
        <h3 id="chat-panel-conduct" class="t-label text-accent-text">{{ ChatCopy::PANEL_CONDUCT }}</h3>
        <ol class="chat-panel__list">
            @foreach ($conduct as $index => $rule)
                <li class="chat-panel__rule"><span class="smallcaps text-accent-text" aria-hidden="true">{{ app(\App\Services\TypesetterService::class)->roman($index + 1) }}</span><span>{{ $rule }}</span></li>
            @endforeach
        </ol>
    </section>

    <section class="chat-panel__section" aria-labelledby="chat-panel-pinned" data-pins>
        <h3 id="chat-panel-pinned" class="t-label text-accent-text">{{ ChatCopy::PANEL_PINNED }}</h3>
        <p class="mt-2 text-sm font-light text-ink-muted" x-show="pins.length === 0">{{ ChatCopy::NO_PINS }}</p>
        <ol class="chat-panel__list" x-show="pins.length > 0">
            <template x-for="p in pins" :key="p.id">
                <li>
                    <button type="button" class="chat-pin" x-on:click="jumpTo(p.id)" data-pin-link>
                        <span class="smallcaps text-ink" x-text="p.author"></span>
                        <time class="chat-time ml-2" :datetime="p.created_at" x-text="formatDateTime(p.created_at)"></time>
                        <span class="chat-pin__excerpt" x-text="p.excerpt"></span>
                    </button>
                    <button type="button" class="chat-action ml-3" x-show="canModerate" x-on:click="unpin({ id: p.id })" data-unpin-panel>{{ ChatCopy::ACTION_UNPIN }}</button>
                </li>
            </template>
        </ol>
    </section>

    <section class="chat-panel__section" aria-labelledby="chat-panel-members" data-members>
        <h3 id="chat-panel-members" class="t-label text-accent-text">{{ ChatCopy::PANEL_MEMBERS }} <span class="ml-1 font-mono normal-case tracking-normal" x-text="members.length"></span></h3>
        <ul class="chat-panel__list">
            <template x-for="member in members" :key="member.id">
                <li class="chat-member">
                    <span class="smallcaps text-ink" x-text="member.name"></span>
                    <span class="t-label text-accent-text" x-show="member.id === viewer.id">{{ ChatCopy::STATUS_YOU }}</span>
                    <span class="chat-member__affiliation" x-show="member.affiliation" x-text="member.affiliation"></span>
                </li>
            </template>
        </ul>
    </section>
</aside>
