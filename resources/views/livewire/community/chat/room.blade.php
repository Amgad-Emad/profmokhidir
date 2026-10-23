@use('App\Constants\ChatSettings')
@use('App\Constants\ChatCopy')
{{--
    The correspondence room. The Livewire component carries requests; the chat
    script (resources/js/chat.js) renders the conversation from `initial` and
    the poll. Three areas from 1280px (rail, conversation, panel), a drawer for
    the panel below that, and one screen at a time on a phone.
--}}
<div class="chat-page"
     x-data="chatRoom({
        channelId: {{ $channelId }},
        channelSlug: @js($channelSlug),
        initial: @js($initial),
        config: @js($config),
        accessRevoked: @js($accessRevoked),
        viewer: @js(['id' => auth()->id(), 'name' => auth()->user()->name, 'affiliation' => auth()->user()->affiliation, 'initials' => \App\Support\Initials::of(auth()->user()->name)]),
        canModerate: @js($canModerate),
        notices: @js(['suspendedHeading' => $suspendedHeading, 'suspended' => $suspendedMessage]),
     })"
     x-on:visibilitychange.document="onVisibility()"
     x-on:chat-request-error.window="onRequestError($event.detail)"
     x-on:popstate.window="onPopState($event)"
     x-on:keydown.escape.window="onEscape()"
     data-chat-room
     {{-- The poll calls a Livewire JS action (registered by the chat script) so the client's own cursor is used. --}}
     @unless ($accessRevoked) wire:poll.{{ ChatSettings::pollIntervalSeconds() }}s="$js.tick" @endunless>

    @if ($accessRevoked)
        {{-- Rendered on the server when the member no longer has access when the page loads. --}}
        @include('livewire.community.chat.partials.suspended', ['live' => false])
    @else
        {{-- Shown by the script when access is taken away mid-session (next poll or send). --}}
        @include('livewire.community.chat.partials.suspended', ['live' => true])

        <div class="chat-shell" :class="{ 'is-channels': mobileView === 'channels', 'has-panel': panelOpen }" x-show="!accessLost" data-chat-shell>
            @include('livewire.community.chat.partials.rail')
            @include('livewire.community.chat.partials.conversation')
            @include('livewire.community.chat.partials.panel')
        </div>

        @include('livewire.community.chat.partials.report-dialog')
    @endif
</div>
