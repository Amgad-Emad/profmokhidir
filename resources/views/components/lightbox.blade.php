{{--
    <x-lightbox :label="$album->title" />
    Native <dialog> driven by the Alpine component `lightbox` (resources/js/lightbox.js) on an ancestor.
    Keyboard: arrows move, Escape closes, Tab is trapped. Touch: swipe left/right. Fades only.
--}}
@props(['label' => 'Photographs'])
<dialog
    x-ref="dialog"
    class="lightbox on-dark"
    aria-label="{{ $label }}"
    @close="onClose()"
    @keydown="onKeydown($event)"
    @click="onBackdropClick($event)"
    @touchstart.passive="onTouchStart($event)"
    @touchend.passive="onTouchEnd($event)"
>
    <div class="lightbox__inner" x-ref="inner">
        <div class="lightbox__bar">
            <p class="t-label text-brass" aria-live="polite" x-text="counter()"></p>
            <button type="button" x-ref="close" class="lightbox__control" @click="close()">
                <span class="t-label">Close</span>
                <svg class="h-3 w-3" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true" focusable="false"><path d="M1 1l10 10M11 1L1 11" /></svg>
            </button>
        </div>

        <figure class="lightbox__figure">
            <div class="lightbox__frame">
                <img
                    x-ref="image"
                    class="lightbox__image"
                    :class="[current.treatment, loaded ? 'is-loaded' : '']"
                    :src="current.src || null"
                    :srcset="current.srcset || null"
                    sizes="100vw"
                    :alt="current.alt || ''"
                    :width="current.width || null"
                    :height="current.height || null"
                    decoding="async"
                    @load="loaded = true"
                >
            </div>
            <figcaption class="lightbox__caption">
                <span class="text-base font-light text-champagne" x-show="current.caption" x-text="current.caption"></span>
                <span class="t-label text-brass" x-show="current.meta" x-text="current.meta"></span>
            </figcaption>
        </figure>

        <button type="button" class="lightbox__arrow lightbox__arrow--prev" @click="prev()" x-show="items.length > 1">
            <span class="sr-only">Previous photograph</span>
            <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true" focusable="false"><path d="M8 1 3 6l5 5" /></svg>
        </button>
        <button type="button" class="lightbox__arrow lightbox__arrow--next" @click="next()" x-show="items.length > 1">
            <span class="sr-only">Next photograph</span>
            <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true" focusable="false"><path d="m4 1 5 5-5 5" /></svg>
        </button>
    </div>
</dialog>
