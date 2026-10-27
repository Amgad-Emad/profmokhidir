{{--
    A marginal note, placed right after the words it annotates. The reference is a small
    numbered button (aria-describedby the note); the note sits in the right margin when the
    surrounding .sidenote-host is wide enough, and is revealed inline on tap otherwise.
    Without JavaScript the note is always shown inline. Kept on one line so no stray
    whitespace enters the sentence.
--}}
@props(['number' => null, 'id' => null])
@php
    $marker = $number ?? '*';
    $noteId = $id ?? 'sidenote-'.$marker.'-'.substr(md5((string) $slot), 0, 8);
@endphp
<span {{ $attributes->class(['sidenote']) }} x-data="{ open: false }"><button type="button" class="sidenote__ref" aria-describedby="{{ $noteId }}" aria-controls="{{ $noteId }}" aria-expanded="false" x-bind:aria-expanded="open.toString()" x-on:click="open = ! open"><span class="sr-only">{{ __('Note') }} </span>{{ $marker }}</button><span id="{{ $noteId }}" class="sidenote__note" role="note" x-bind:class="{ 'is-open': open }"><span class="sidenote__number" aria-hidden="true">{{ $marker }}</span>{{ $slot }}</span></span>
