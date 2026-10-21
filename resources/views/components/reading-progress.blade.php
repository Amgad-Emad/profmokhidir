{{--
    A slim brass line under the header that grows with the reader's progress through
    `target` (a CSS selector). It follows the scroll directly, so it reads the same with
    reduced motion, and it is decorative: screen readers never meet it.
--}}
@props(['target'])
<div {{ $attributes->class(['reading-progress']) }} x-data="readingProgress" data-target="{{ $target }}" aria-hidden="true"></div>
