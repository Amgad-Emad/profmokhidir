@use('App\Constants\ChatSettings')
@use('App\Constants\ChatCopy')
{{--
    The one Scientific Community room. The Livewire component carries requests; the chat
    script (resources/js/chat.js) renders the conversation from `initial` and the poll. The
    conversation fills the centre and "About this room" (About, Pinned, Members) is a
    collapsible column on the right from 1024px, a drawer below that. The shell is
    wire:ignore and the component never re-renders on an action, so a poll can never touch
    what a member is typing.
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

        <div class="chat-shell" :class="{ 'has-panel': panelOpen }" x-show="!accessLost" wire:ignore data-chat-shell>
            @include('livewire.community.chat.partials.conversation')
            @include('livewire.community.chat.partials.panel')
            @include('livewire.community.chat.partials.report-dialog')
            @include('livewire.community.chat.partials.delete-dialog')

            {{-- The site's lightbox, fed with the images of the message that was opened. --}}
            <div x-data="chatLightbox" x-on:chat-lightbox.window="openWith($event.detail)" data-chat-lightbox>
                <x-lightbox :label="__(ChatCopy::ATTACH_GALLERY_LABEL)" />
            </div>
        </div>
    @endif
</div>
