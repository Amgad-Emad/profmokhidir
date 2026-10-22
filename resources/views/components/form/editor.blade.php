{{--
    The rich text editor (Tiptap) inside the shared frame: one focus frame on the wrapper, never
    an outline inside it. The toolbar (App\Constants\FormControls::editorTools) has labelled
    icons with tooltips that name the keyboard shortcut, shows which styles are active, and is
    one Tab stop (arrow keys move along it, as the ARIA toolbar pattern does). Link opens a
    small dialog that checks the address before applying it. A word count sits in the footer.
    Livewire binds `model` (the HTML is sanitised again on save by App\Support\RichText); plain
    forms get a hidden input named `name`. Without JavaScript a plain textarea takes its place.
    Alpine component `formEditor` (resources/js/forms/editor.js, Tiptap loaded on demand).
--}}
@props([
    'name' => null,
    'model' => null,
    'label',
    'value' => null,
    'hint' => null,
    'required' => false,
    'optional' => null,
    'id' => null,
    'bag' => 'default',
    'error' => null,
    'placeholder' => 'Start writing…',
])
@use('App\Constants\FormControls')
@inject('forms', 'App\Services\FormFieldService')
@php
    $field = $forms->describe(['error' => $error, 
        'name' => $name, 'model' => $model, 'wire' => '', 'id' => $id, 'label' => $label,
        'hint' => $hint, 'required' => $required, 'optional' => $optional, 'bag' => $bag,
    ], $errors ?? null);
    $editorId = $field['id'].'-editor';
    $linkId = $field['id'].'-link';
    $initial = $model === null ? (string) old($field['key'], $value ?? '') : null;
    $groups = collect(FormControls::editorTools())->groupBy('group', preserveKeys: true);
    $config = [
        'model' => $model,
        'content' => $initial,
        'labelId' => $field['id'].'-label',
        'describedBy' => $field['describedBy'],
        'invalid' => $field['invalid'],
        'placeholder' => $placeholder,
        'protocols' => FormControls::LINK_PROTOCOLS,
        'linkInvalid' => FormControls::LINK_INVALID,
        'words' => [FormControls::WORD_COUNT_SINGULAR, FormControls::WORD_COUNT_PLURAL],
    ];
@endphp
<x-form.field :field="$field" :class="$attributes->get('class')" data-rich-text>
    <div class="form-control form-control--editor" x-data="formEditor({{ \Illuminate\Support\Js::from($config) }})" @if ($model) wire:ignore @endif x-cloak
         x-bind:data-filled="words > 0 ? 'true' : 'false'">
        <div class="form-editor__toolbar" role="toolbar" aria-label="{{ FormControls::EDITOR_TOOLBAR }}" aria-controls="{{ $editorId }}" x-on:keydown="toolbarKey($event)">
            @foreach ($groups as $group => $tools)
                <div class="form-editor__group" role="group">
                    @foreach ($tools as $command => $tool)
                        @php $shortcut = FormControls::shortcutLabel($tool['shortcut']); @endphp
                        <button type="button" class="form-editor__tool" data-tool="{{ $command }}" tabindex="{{ $loop->parent->first && $loop->first ? '0' : '-1' }}"
                                aria-label="{{ $tool['label'] }}" aria-keyshortcuts="{{ str_replace('Ctrl', 'Control', $shortcut) }}"
                                @if (! in_array($command, ['undo', 'redo'], true)) aria-pressed="false" x-bind:aria-pressed="is('{{ $command }}')" @else x-bind:disabled="!can('{{ $command }}')" @endif
                                @if ($command === 'link') aria-haspopup="dialog" x-bind:aria-expanded="linking ? 'true' : 'false'" aria-controls="{{ $linkId }}" @endif
                                x-on:click="run('{{ $command }}')">
                            <x-icon :name="$tool['icon']" class="size-5" />
                            <span class="form-editor__tip" aria-hidden="true">{{ $tool['label'] }}<kbd data-shortcut>{{ $shortcut }}</kbd></span>
                        </button>
                    @endforeach
                </div>
            @endforeach
        </div>

        <div id="{{ $linkId }}" class="form-editor__dialog" role="dialog" aria-modal="false" aria-labelledby="{{ $linkId }}-title" x-show="linking" x-cloak x-on:keydown.escape.prevent.stop="closeLink()">
            <p id="{{ $linkId }}-title" class="t-label text-accent-text">{{ FormControls::LINK_DIALOG_TITLE }}</p>
            <div class="form-field mt-3">
                <label for="{{ $linkId }}-url" class="form-label t-label">{{ FormControls::LINK_URL_LABEL }}</label>
                <p id="{{ $linkId }}-hint" class="form-hint">{{ FormControls::LINK_URL_HINT }}</p>
                <div class="form-field__control">
                    <div class="form-control form-control--compact">
                        <input id="{{ $linkId }}-url" type="url" class="form-control__input" placeholder="https://" x-ref="linkInput" x-model="linkUrl"
                               x-bind:aria-invalid="linkError ? 'true' : null" aria-describedby="{{ $linkId }}-hint {{ $linkId }}-error"
                               x-on:keydown.enter.prevent="applyLink()" x-on:blur="checkLink()">
                    </div>
                </div>
                <p id="{{ $linkId }}-error" class="form-message form-error" x-show="linkError" x-cloak role="alert"><x-icon name="warning" class="mt-1 size-4 shrink-0" /><span x-text="linkError"></span></p>
            </div>
            <div class="form-editor__dialog-actions">
                <x-button type="button" variant="primary" class="px-5 py-2 text-sm" x-on:click="applyLink()">{{ FormControls::LINK_APPLY }}</x-button>
                <button type="button" class="form-file__action" x-show="is('link') === 'true'" x-on:click="removeLink()">{{ FormControls::LINK_REMOVE }}</button>
                <button type="button" class="form-file__action" x-on:click="closeLink()">{{ FormControls::LINK_CANCEL }}</button>
            </div>
        </div>

        <div class="form-editor__content prose-bio" style="--editor-placeholder: {{ json_encode($placeholder, JSON_UNESCAPED_UNICODE) }}">
            <div id="{{ $editorId }}" x-ref="editor"></div>
        </div>
        <div class="form-editor__footer"><span aria-live="off" x-text="wordsLabel" data-word-count></span></div>
        @if ($model === null && $name !== null)
            <input type="hidden" name="{{ $name }}" x-ref="value" value="{{ $initial }}">
        @endif
    </div>
    <noscript>
        <div class="form-control">
            <textarea id="{{ $field['id'] }}" rows="12" class="form-control__input form-control__input--mono" @if ($name) name="{{ $name }}" @endif @if ($model) wire:model.live.blur="{{ $model }}" @endif>{{ $initial }}</textarea>
        </div>
    </noscript>
</x-form.field>
