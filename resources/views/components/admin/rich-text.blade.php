{{--
    Tiptap editor bound to a Livewire property (headings, bold, italic, links, lists,
    quotes only). Alpine component `richText` lives in resources/js/admin.js. The HTML is
    sanitised again on save (App\Support\RichText). Without JavaScript the plain textarea
    below is used instead.
--}}
@props(['name', 'label', 'hint' => null])
@php
    $id = str_replace(['.', '_'], '-', $name);
    $errorId = $id.'-error';
@endphp
<div {{ $attributes->only('class') }} data-rich-text>
    <p id="{{ $id }}-label" class="t-label text-bronze">{{ $label }}</p>
    @if ($hint)
        <p class="mt-2 text-sm font-light text-ash">{{ $hint }}</p>
    @endif
    <div class="rich-text mt-2 rounded-card border border-stone bg-ivory" x-data="richText('{{ $name }}')" wire:ignore x-cloak x-show="ready">
        <div class="rich-text__toolbar" role="toolbar" aria-label="Formatting" aria-controls="{{ $id }}-editor">
            <button type="button" x-on:click="run('heading2')" x-bind:aria-pressed="is('heading2')" title="Heading"><span aria-hidden="true">H2</span><span class="sr-only">Heading</span></button>
            <button type="button" x-on:click="run('heading3')" x-bind:aria-pressed="is('heading3')" title="Subheading"><span aria-hidden="true">H3</span><span class="sr-only">Subheading</span></button>
            <button type="button" x-on:click="run('bold')" x-bind:aria-pressed="is('bold')" title="Bold"><strong aria-hidden="true">B</strong><span class="sr-only">Bold</span></button>
            <button type="button" x-on:click="run('italic')" x-bind:aria-pressed="is('italic')" title="Italic"><em aria-hidden="true">I</em><span class="sr-only">Italic</span></button>
            <button type="button" x-on:click="run('bulletList')" x-bind:aria-pressed="is('bulletList')" title="Bulleted list"><span aria-hidden="true">•</span><span class="sr-only">Bulleted list</span></button>
            <button type="button" x-on:click="run('orderedList')" x-bind:aria-pressed="is('orderedList')" title="Numbered list"><span aria-hidden="true">1.</span><span class="sr-only">Numbered list</span></button>
            <button type="button" x-on:click="run('blockquote')" x-bind:aria-pressed="is('blockquote')" title="Quote"><span aria-hidden="true">“</span><span class="sr-only">Quote</span></button>
            <button type="button" x-on:click="toggleLinkForm()" x-bind:aria-pressed="is('link')" x-bind:aria-expanded="linking.toString()" title="Link"><span aria-hidden="true">⌁</span><span class="sr-only">Link</span></button>
            <div class="rich-text__link" x-show="linking" x-cloak>
                <label class="sr-only" for="{{ $id }}-link-url">Link address</label>
                <input id="{{ $id }}-link-url" type="url" x-model="linkUrl" x-ref="linkInput" placeholder="https://" x-on:keydown.enter.prevent="setLink()" x-on:keydown.escape.prevent="linking = false">
                <button type="button" x-on:click="setLink()">Apply</button>
                <button type="button" x-on:click="unsetLink()">Remove</button>
            </div>
        </div>
        <div id="{{ $id }}-editor" x-ref="editor" class="rich-text__content prose-bio" data-label-id="{{ $id }}-label"></div>
    </div>
    <noscript>
        <textarea id="{{ $id }}" rows="12" wire:model.blur="{{ $name }}" class="mt-2 block w-full rounded-card border border-stone bg-ivory px-4 py-3 font-mono text-sm text-evergreen"></textarea>
    </noscript>
    @error($name)
        <p id="{{ $errorId }}" class="mt-2 flex items-center gap-2 text-sm text-bronze" role="alert"><x-icon name="warning" class="size-4 shrink-0" /><span>{{ $message }}</span></p>
    @enderror
</div>
