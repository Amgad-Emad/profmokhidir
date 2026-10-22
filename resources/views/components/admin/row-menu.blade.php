{{--
    The actions of one table row in a small menu: a "More" button (named after the row for screen
    readers) that opens a short list of links and buttons. Escape and a click outside close it.
    Items use .row-menu__item; put destructive ones last.
--}}
@props(['label'])
<div class="row-menu" x-data="{ open: false }" x-on:keydown.escape="if (open) { open = false; $refs.button.focus() }" x-on:click.outside="open = false" data-row-menu>
    <button type="button" class="row-menu__button" x-ref="button" x-on:click="open = !open" aria-expanded="false" x-bind:aria-expanded="open.toString()">
        <x-icon name="more" class="size-5" /><span class="sr-only">Actions for {{ $label }}</span>
    </button>
    <div class="row-menu__panel" x-show="open" x-cloak x-on:click="open = false">
        {{ $slot }}
    </div>
</div>
