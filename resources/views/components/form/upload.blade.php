{{--
    File and image upload: a drop zone (the real file input covers it, so a click, the keyboard
    or a drop all reach it), a progress hairline with the percentage while a Livewire upload
    runs, then the file as a card with a preview (images), its name, type and size, and
    Replace / Remove. `media` is the file already attached (a Spatie Media), with `href` to open
    it and `remove` the Livewire method that detaches it. With `image` and `alt` (a Livewire
    property), the alt text field is asked for as soon as an image is chosen. Plain forms pass
    `name`; previews and the file name still show, the progress line does not. Alpine component
    `formUpload` (forms.js).
--}}
@props([
    'name' => null,
    'model' => null,
    'label',
    'hint' => null,
    'required' => false,
    'optional' => null,
    'accept' => null,
    'multiple' => false,
    'image' => false,
    'media' => null,
    'href' => null,
    'remove' => null,
    'alt' => null,
    'altRequired' => false,
    'id' => null,
    'bag' => 'default',
    'error' => null,
])
@use('App\Constants\FormControls')
@inject('forms', 'App\Services\FormFieldService')
@php
    $field = $forms->describe(['error' => $error, 
        'name' => $name, 'model' => $model, 'wire' => '', 'id' => $id, 'label' => $label,
        'hint' => $hint, 'required' => $required, 'optional' => $optional, 'bag' => $bag, 'nested' => true,
    ], $errors ?? null);
    $fileAttributes = $forms->controlAttributes($field, array_merge(
        ['type' => 'file', 'accept' => $accept, 'multiple' => $multiple, 'data-label' => null],
    ), $attributes->except(['class'])->getAttributes());
    $currentName = $media?->file_name;
    $currentMeta = $media ? strtoupper((string) pathinfo((string) $media->file_name, PATHINFO_EXTENSION)).' · '.\App\Support\FileSize::format((int) $media->size) : null;
    $currentPreview = $media && $image ? $media->getUrl() : null;
    $copy = [
        'progress' => FormControls::UPLOAD_PROGRESS,
        'done' => FormControls::UPLOAD_DONE,
        'failed' => FormControls::UPLOAD_FAILED,
    ];
@endphp
<x-form.field :field="$field" :class="$attributes->get('class')" x-data="formUpload({{ \Illuminate\Support\Js::from(['image' => (bool) $image, 'name' => $currentName, 'meta' => $currentMeta, 'preview' => $currentPreview, 'copy' => $copy]) }})" data-upload-field>
    <div class="form-drop" x-bind:data-dragging="dragging ? 'true' : 'false'" x-on:dragenter="dragging = true" x-on:dragleave="dragging = false" x-on:drop="dragging = false"
         @if ($model) x-on:livewire-upload-start="start()" x-on:livewire-upload-progress="progress($event.detail.progress)" x-on:livewire-upload-finish="finish()" x-on:livewire-upload-error="fail()" x-on:livewire-upload-cancel="fail()" @endif>
        <x-icon :name="$image ? 'image' : 'upload'" class="form-drop__icon size-6" />
        <p><span>{{ $multiple && $image ? FormControls::UPLOAD_DROP_IMAGES : FormControls::UPLOAD_DROP }}</span> <span class="form-drop__choose">{{ $multiple && $image ? FormControls::UPLOAD_CHOOSE_IMAGES : FormControls::UPLOAD_CHOOSE }}</span></p>
        <input class="form-drop__input" {{ $fileAttributes }} x-ref="input" x-on:change="chosen($event)">
    </div>

    <div class="form-progress" x-show="uploading" x-cloak role="status">
        <span class="form-progress__track" aria-hidden="true"><span class="form-progress__bar" x-bind:style="'--progress: ' + percent"></span></span>
        <span x-text="progressLabel"></span>
    </div>
    <p class="form-message form-note" x-show="note !== ''" x-text="note" x-cloak aria-live="polite"></p>

    <div class="form-file" x-show="fileName" @if (! $currentName) x-cloak @endif data-current-file>
        <template x-if="previewUrl"><img x-bind:src="previewUrl" alt="" class="form-file__preview"></template>
        @unless ($image)<x-icon name="file" class="size-6 text-ink-muted" />@endunless
        <div class="form-file__body">
            <p class="form-file__name" x-text="fileName">{{ $currentName }}</p>
            <p class="form-file__meta" x-text="fileMeta">{{ $currentMeta }}</p>
        </div>
        <div class="form-file__actions">
            @if ($href)
                <a href="{{ $href }}" target="_blank" rel="noopener" class="form-file__action">Open<span class="sr-only"> {{ $currentName }} (opens in a new tab)</span></a>
            @endif
            <x-button variant="quiet" size="sm" type="button" x-on:click="$refs.input.click()">{{ FormControls::UPLOAD_REPLACE }}<span class="sr-only"> {{ \Illuminate\Support\Str::lower($label) }}</span></x-button>
            @if ($remove)
                <x-button variant="quiet" size="sm" danger type="button" wire:click="{{ $remove }}" x-on:click="clear()">{{ FormControls::UPLOAD_REMOVE }}<span class="sr-only"> {{ \Illuminate\Support\Str::lower($label) }}</span></x-button>
            @elseif ($model === null)
                <x-button variant="quiet" size="sm" danger type="button" x-on:click="clear()">{{ FormControls::UPLOAD_REMOVE }}<span class="sr-only"> {{ \Illuminate\Support\Str::lower($label) }}</span></x-button>
            @endif
        </div>
    </div>

    @if ($image && $alt)
        <div class="mt-6" x-show="fileName" @if (! $currentName) x-cloak @endif data-alt-prompt>
            <x-form.input :model="$alt" label="Alt text" :hint="FormControls::UPLOAD_ALT_PROMPT" :required="$altRequired" />
        </div>
    @endif
    {{ $slot }}
</x-form.field>
