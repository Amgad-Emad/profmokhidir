@use('App\Constants\ChatSettings')
<div class="chat-page mx-auto max-w-7xl px-4 pb-6 pt-6 sm:px-6"
     x-data="chatRoom({
        channelId: {{ $channelId }},
        channelSlug: @js($channelSlug),
        initial: @js($initial),
        config: @js($config),
        accessRevoked: @js($accessRevoked),
        viewer: @js(['id' => auth()->id(), 'name' => auth()->user()->name, 'affiliation' => auth()->user()->affiliation]),
        canModerate: @js($canModerate),
        notices: @js(['suspendedHeading' => $suspendedHeading, 'suspended' => $suspendedMessage]),
     })"
     x-on:visibilitychange.document="onVisibility()"
     x-on:chat-request-error.window="onRequestError($event.detail)"
     data-chat-room
     {{-- The poll calls a Livewire JS action (registered by the chat script) so the client's own cursor is used. --}}
     @unless ($accessRevoked) wire:poll.{{ ChatSettings::pollIntervalSeconds() }}s="$js.tick" @endunless>

    @if ($accessRevoked)
        {{-- Rendered on the server when the member no longer has access when the page loads. --}}
        <section class="mx-auto max-w-2xl py-16" aria-labelledby="suspended-title" data-access-revoked>
            <x-section-header label="Scientific Community" :title="$suspendedHeading" level="1" id="suspended-title">
                {{ $suspendedMessage }}
            </x-section-header>
            <x-button variant="outline" :href="$communityUrl" class="mt-8">Back to the community page</x-button>
        </section>
    @else
        {{-- Shown by the script when access is taken away mid-session (next poll or send). --}}
        <section class="mx-auto max-w-2xl py-16" aria-labelledby="suspended-title-live" x-show="accessLost" x-cloak data-access-lost>
            <p class="t-label text-bronze">Scientific Community</p>
            <hr class="rule-brass mt-4 is-drawn">
            <h1 id="suspended-title-live" class="t-heading mt-8">{{ $suspendedHeading }}</h1>
            <p class="t-body mt-6 max-w-2xl text-ash">{{ $suspendedMessage }}</p>
            <x-button variant="outline" :href="$communityUrl" class="mt-8">Back to the community page</x-button>
        </section>

        <div class="chat-shell lg:grid lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-8" x-show="!accessLost">
            {{-- Channels --}}
            <aside class="chat-channels mb-6 lg:mb-0" aria-labelledby="channels-heading">
                <p id="channels-heading" class="t-label text-bronze">Channels</p>
                <ul class="mt-4 flex gap-2 overflow-x-auto lg:flex-col" data-channels>
                    <template x-for="channel in channels" :key="channel.id">
                        <li class="shrink-0">
                            <a :href="channelUrl(channel)" class="chat-channel-link" :aria-current="channel.current ? 'page' : null" :data-channel="channel.slug">
                                <span class="truncate" x-text="channel.name"></span>
                                <span class="chat-unread-count" x-show="channel.unread > 0" x-text="channel.unread" :aria-label="channel.unread + ' unread'"></span>
                            </a>
                        </li>
                    </template>
                </ul>
            </aside>

            {{-- Conversation --}}
            <section class="chat-room flex min-h-0 flex-col rounded-card border border-stone bg-linen" aria-labelledby="room-heading">
                <header class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-b border-stone px-5 py-4">
                    <div>
                        <h1 id="room-heading" class="font-display text-3xl font-light leading-tight">{{ $channelName }}</h1>
                        <p class="mt-1 text-sm font-light text-ash">{{ $initial['channels'][array_search(true, array_column($initial['channels'], 'current'), true) ?: 0]['description'] ?? $chatIntro }}</p>
                    </div>
                    <p class="chat-status text-sm font-light text-ash" role="status" aria-live="polite">
                        <span x-show="expired" x-cloak>Your session has expired. <a href="{{ route('login') }}" class="text-evergreen underline decoration-brass underline-offset-4">Sign in again</a>.</span>
                        <span x-show="!expired && offline" x-cloak class="inline-flex items-center gap-2"><x-icon name="clock" class="size-4" />Reconnecting…</span>
                        <span x-show="!expired && !offline && notice" x-cloak x-text="notice"></span>
                    </p>
                </header>

                <div class="chat-scroller relative min-h-0 flex-1" x-ref="scroller" x-on:scroll.passive="onScroll()" tabindex="0" aria-label="Messages" data-scroller>
                    <div class="py-3 text-center" x-show="hasOlder">
                        <button type="button" class="filter-pill" x-on:click="loadOlder()" x-bind:disabled="loadingOlder" data-load-older>
                            <span x-show="!loadingOlder">Load earlier messages</span>
                            <span x-show="loadingOlder" x-cloak>Loading…</span>
                        </button>
                    </div>

                    <p class="px-5 py-12 text-center font-display text-2xl font-light italic text-ash" x-show="messages.length === 0" data-empty>No messages yet. Say hello.</p>

                    <ol class="chat-messages px-5 pb-4" data-messages>
                        <template x-for="group in groups" :key="group.day">
                            <li>
                                <p class="chat-day" x-text="group.label"></p>
                                <ol>
                                    <template x-for="m in group.messages" :key="m.key">
                                        <li class="chat-message" :class="{ 'is-mine': m.mine, 'is-sending': m.state === 'sending', 'is-failed': m.state === 'failed', 'is-deleted': m.deleted }" :data-message-id="m.id" :data-client-uuid="m.client_uuid" :id="'message-' + m.key">
                                            <template x-if="unreadFrom && m.id === unreadFrom">
                                                <p class="chat-unread-divider" data-unread-divider><span>New messages</span></p>
                                            </template>
                                            <div class="chat-message__meta">
                                                <span class="chat-message__author" x-text="m.author"></span>
                                                <span class="chat-message__affiliation" x-show="m.affiliation" x-text="m.affiliation"></span>
                                                <time class="chat-message__time" :datetime="m.created_at" x-text="formatTime(m.created_at)"></time>
                                                <span class="chat-message__flag" x-show="m.edited_at && !m.deleted">edited</span>
                                                <span class="chat-message__flag" x-show="m.state === 'sending'">sending…</span>
                                            </div>

                                            <template x-if="m.deleted">
                                                <p class="chat-message__removed" x-text="config.removedPlaceholder"></p>
                                            </template>

                                            <template x-if="!m.deleted && !(editing && editing.key === m.key)">
                                                <p class="chat-message__body" x-html="m.html"></p>
                                            </template>

                                            <template x-if="editing && editing.key === m.key">
                                                <form class="chat-edit" x-on:submit.prevent="saveEdit()">
                                                    <label class="sr-only" :for="'edit-' + m.key">Edit message</label>
                                                    <textarea :id="'edit-' + m.key" class="chat-edit__input" rows="3" x-model="editing.body" :maxlength="config.bodyMaxLength" x-on:keydown.enter.prevent="if (!$event.shiftKey) saveEdit(); else insertNewline($event)" x-on:keydown.escape.prevent="cancelEdit()" x-init="$el.focus()"></textarea>
                                                    <p class="text-sm text-bronze" x-show="editing.error" x-text="editing.error" role="alert"></p>
                                                    <div class="flex flex-wrap gap-3">
                                                        <button type="submit" class="chat-action" x-bind:disabled="editing.saving">Save</button>
                                                        <button type="button" class="chat-action" x-on:click="cancelEdit()">Cancel</button>
                                                        <button type="button" class="chat-action" x-show="editing.stale" x-on:click="reloadMessage(m)">Reload it</button>
                                                    </div>
                                                </form>
                                            </template>

                                            <p class="chat-message__error" x-show="m.state === 'failed'" role="alert">
                                                Not sent · <button type="button" class="chat-action" x-on:click="retry(m)" data-retry>Retry</button>
                                                <span x-show="m.error" x-text="m.error"></span>
                                            </p>

                                            <div class="chat-message__actions" x-show="!m.deleted && m.id && !(editing && editing.key === m.key) && !expired && !accessLost">
                                                <button type="button" class="chat-action" x-show="m.can_edit" x-on:click="startEdit(m)" data-edit>Edit</button>
                                                <template x-if="m.can_delete">
                                                    <span>
                                                        <button type="button" class="chat-action" x-show="deleting !== m.key" x-on:click="deleting = m.key" data-delete>Delete</button>
                                                        <span x-show="deleting === m.key" x-cloak>Delete this message? <button type="button" class="chat-action" x-on:click="remove(m)" data-delete-confirm>Yes</button> <button type="button" class="chat-action" x-on:click="deleting = null">No</button></span>
                                                    </span>
                                                </template>
                                                <button type="button" class="chat-action" x-show="m.can_report" x-on:click="startReport(m)" data-report>Report</button>
                                            </div>
                                        </li>
                                    </template>
                                </ol>
                            </li>
                        </template>
                    </ol>
                </div>

                <button type="button" class="chat-new-pill" x-show="newCount > 0" x-cloak x-on:click="scrollToBottom(true)" data-new-messages>
                    <span x-text="newCount === 1 ? '1 new message' : newCount + ' new messages'"></span>
                </button>

                {{-- Composer --}}
                <form class="chat-composer border-t border-stone px-5 py-4" x-on:submit.prevent="send()" data-composer>
                    <label for="chat-draft" class="sr-only">Message</label>
                    <textarea id="chat-draft" name="body" rows="2" class="chat-composer__input" placeholder="Write a message… Enter to send, Shift+Enter for a new line."
                              x-model="draft" :maxlength="config.bodyMaxLength" x-ref="draft"
                              x-on:keydown.enter="onEnter($event)"
                              x-bind:disabled="expired || accessLost"></textarea>
                    <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm font-light text-ash" aria-live="polite"><span x-text="draft.length"></span> / <span x-text="config.bodyMaxLength"></span></p>
                        <p class="text-sm text-bronze" role="alert" x-show="sendError" x-text="sendError" x-cloak></p>
                        <x-button type="submit" class="px-5 py-2 text-sm" x-bind:disabled="sending || expired || accessLost || draft.trim() === ''" data-send>
                            <span x-show="!sending">Send</span>
                            <span x-show="sending" x-cloak>Sending…</span>
                        </x-button>
                    </div>
                </form>
            </section>
        </div>

        {{-- Report dialog --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-evergreen/70 p-4" x-show="reporting" x-cloak x-trap.noscroll.inert="reporting" x-on:keydown.escape.window="cancelReport()" data-report-dialog>
            <form role="dialog" aria-modal="true" aria-labelledby="report-title" class="w-full max-w-md rounded-card border border-stone bg-ivory p-6 sm:p-8" x-on:submit.prevent="submitReport()">
                <h2 id="report-title" class="font-display text-3xl font-light leading-tight">Report this message</h2>
                <p class="mt-3 text-base font-light text-ash">Tell the administrators briefly what is wrong. They will see the message, your name and your reason.</p>
                <label for="report-reason" class="t-label mt-6 block text-bronze">Reason</label>
                <textarea id="report-reason" rows="4" class="mt-2 block w-full rounded-card border border-stone bg-ivory px-4 py-3 text-body text-evergreen focus:border-bronze" x-model="reportReason" maxlength="{{ ChatSettings::REPORT_REASON_MAX_LENGTH }}" required></textarea>
                <p class="mt-2 text-sm text-bronze" role="alert" x-show="reportError" x-text="reportError"></p>
                <div class="mt-8 flex flex-wrap justify-end gap-3">
                    <x-button type="button" variant="outline" x-on:click="cancelReport()">Cancel</x-button>
                    <x-button type="submit" x-bind:disabled="reportSending" data-report-submit>Send report</x-button>
                </div>
            </form>
        </div>
    @endif
</div>
