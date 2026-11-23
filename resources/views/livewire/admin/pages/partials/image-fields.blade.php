{{--
    An image block's fields, the same in both language tabs: the upload, then the words (alt text,
    caption, place: this tab's language, `$wordsModel`), then the year and crop focus (one value
    for both languages, always the English form's). `$suffix` keeps the second tab's ids unique.
    Expects $key, $block, $media, $value, $imageHint, $isSeo, $wordsModel, $suffix and $words (dir/hint of the words).
--}}
@php
    $shared = 'form.'.$key;
    $fieldId = fn (string $model): string => str_replace('.', '-', $model).$suffix;
@endphp
<x-form.upload :model="'uploads.'.$key" :id="$fieldId('uploads.'.$key)" :label="$isSeo ? __('Share image') : __($block->label)" :hint="($isSeo ? __('Shown when the page is shared on social media; 1200 × 630 pixels works best.').' ' : ($block->hint ? __($block->hint).' ' : '')).$imageHint" :media="$media" :href="$media?->getUrl()" :remove="$media ? 'removeImage(\''.$key.'\')' : null" accept="image/jpeg,image/png,image/webp" :image="true" />
@if ($media)
    <div class="form-grid" data-image-fields="{{ $key }}">
        <x-form.input :model="$wordsModel.'.alt_text'" :id="$fieldId($wordsModel.'.alt_text')" :label="__('Alt text')" :hint="$words['altHint']" />
        @unless ($isSeo)
            <x-form.input :model="$wordsModel.'.caption'" :id="$fieldId($wordsModel.'.caption')" :label="__('Caption')" />
            <div class="form-row">
                <x-form.input :model="$wordsModel.'.place'" :id="$fieldId($wordsModel.'.place')" :label="__('Place')" class="form-col--two-thirds" />
                <x-form.year :model="$shared.'.year'" :id="$fieldId($shared.'.year')" :label="__('Year')" class="form-col--third" />
            </div>
            <fieldset class="form-fieldset" data-focus-point>
                <legend class="t-label form-label">{{ __('Crop focus') }}</legend>
                <p class="form-hint">{{ __('Where the frame centres when it crops the image. Click the point that must stay in view, or set it in percent.') }}</p>
                <div class="focus-picker mt-4" dir="ltr" x-data x-on:click="const r = $el.getBoundingClientRect(); $wire.set('{{ $shared }}.focus_x', Math.round(($event.clientX - r.left) / r.width * 100)); $wire.set('{{ $shared }}.focus_y', Math.round(($event.clientY - r.top) / r.height * 100))">
                    <img src="{{ $media->getUrl() }}" alt="" class="focus-picker__image">
                    <span class="focus-picker__mark" aria-hidden="true" style="left: {{ (int) ($value['focus_x'] ?? 50) }}%; top: {{ (int) ($value['focus_y'] ?? 50) }}%"></span>
                </div>
                <div class="form-row mt-4">
                    <x-form.input class="form-col--half" :model="$shared.'.focus_x'" :id="$fieldId($shared.'.focus_x')" :label="__('From the left (%)')" type="number" min="0" max="100" inputmode="numeric" :optional="false" />
                    <x-form.input class="form-col--half" :model="$shared.'.focus_y'" :id="$fieldId($shared.'.focus_y')" :label="__('From the top (%)')" type="number" min="0" max="100" inputmode="numeric" :optional="false" />
                </div>
            </fieldset>
        @endunless
    </div>
@endif
